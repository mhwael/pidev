<?php

namespace App\Service;

class LlmChatbotService
{
    public function __construct(
        private GeminiClient $gemini,
        private ShopToolsService $tools,
    ) {}

    /**
     * @return array{
     *   reply: string,
     *   cards: list<array<string, mixed>>,
     *   suggestions: list<string>
     * }
     */
    public function chat(string $userMessage): array
    {
        $raw = trim($userMessage);
        $m = mb_strtolower($raw);

        $quick = $this->quickSmallTalk($m);
        if ($quick !== null) {
            return [
                'reply' => $quick['reply'],
                'cards' => [],
                'suggestions' => $quick['suggestions'],
            ];
        }

        $shopIntent = $this->isShopIntent($m);

        if ($shopIntent) {
            $snapshot = $this->buildShopSnapshot($raw);

            $system = $this->shopSystemPrompt();
            $userContent = "User: {$raw}\n\nSHOP_SNAPSHOT:\n" . json_encode($snapshot, JSON_UNESCAPED_UNICODE);

            $reply = $this->callGemini($system, $userContent);
            if ($reply === '') {
                $reply = $this->fallbackShop($raw);
            }

            return [
                'reply' => $reply,
                'cards' => $snapshot['cards'],
                'suggestions' => $this->shopSuggestions($m),
            ];
        }

        $system = $this->generalSystemPrompt();
        $reply = $this->callGemini($system, $raw);

        if ($reply === '') {
            $reply = "D’accord 🙂 Dis-moi ce que tu veux, je t’écoute.";
        }

        return [
            'reply' => $reply,
            'cards' => [],
            'suggestions' => ["Sujet libre", "Raconte-moi", "Aide pour la boutique"],
        ];
    }

    private function callGemini(string $system, string $userText): string
    {
        try {
            $resp = $this->gemini->generateText($system, [
                ['role' => 'user', 'content' => $userText],
            ], 15);

            if ($resp['status'] === 429) {
                return "Je suis un peu surchargé (limite API atteinte) 😅. Réessaie dans 1–2 minutes.";
            }

            if ($resp['status'] >= 400) {
                return '';
            }

            return trim($resp['text']);
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function generalSystemPrompt(): string
    {
        return <<<TXT
You are a friendly ChatGPT-like assistant.
You can discuss ANY topic (life, jokes, study, tech, etc.).
Reply in the SAME language style as the user:
- English
- French
- Tunisian Derja (prefer Arabizi 3/7/9)
If mixed, reply mixed.
Be natural and conversational.
TXT;
    }

    private function shopSystemPrompt(): string
    {
        return <<<TXT
You are "LevelUp Assistant", a helpful assistant for a gaming e-commerce shop.
Reply in the SAME language style as the user (English/French/Derja Arabizi).
Use only the real data provided in SHOP_SNAPSHOT for product facts.

Rules:
- Do NOT invent products.
- Use SHOP_SNAPSHOT to answer popular products, forecasts (7d), low stock, recommendations, budget search.
- Keep answers short & clean, end with 1 question (budget/platform/category).
TXT;
    }

    /**
     * @return array{
     *   popular: list<array<string, mixed>>,
     *   predicted: list<array<string, mixed>>,
     *   low_stock: list<array<string, mixed>>,
     *   recommendations: list<array<string, mixed>>,
     *   cards: list<array<string, mixed>>
     * }
     */
    private function buildShopSnapshot(string $raw): array
    {
        $m = mb_strtolower($raw);

        $popular = $this->tools->dispatch('get_popular_products', ['limit' => 5]);
        $pred    = $this->tools->dispatch('get_top_predicted_next7d', ['limit' => 5]);
        $low     = $this->tools->dispatch('get_low_stock', ['threshold' => 5, 'limit' => 5]);

        /** @var list<array<string, mixed>> $cards */
        $cards = array_merge($popular['cards'] ?? [], $pred['cards'] ?? [], $low['cards'] ?? []);

        $recoItems = [];
        if (str_contains($m, 'recomm') && preg_match('/(?:produit|product)\s*#?\s*(\d+)/i', $raw, $mm)) {
            $reco = $this->tools->dispatch('get_recommendations_for_product', [
                'product_id' => (int) $mm[1],
                'k' => 6,
            ]);

            $recoItems = $reco['items'] ?? [];
            $cards = array_merge($cards, $reco['cards'] ?? []);
        }

        $cards = array_values(array_slice($cards, 0, 6));

        return [
            'popular' => $popular['items'] ?? [],
            'predicted' => $pred['items'] ?? [],
            'low_stock' => $low['items'] ?? [],
            'recommendations' => $recoItems,
            'cards' => $cards,
        ];
    }

    /**
     * @return array{reply:string, suggestions:list<string>}|null
     */
    private function quickSmallTalk(string $m): ?array
    {
        if ($this->isGoodbye($m)) {
            return ['reply' => "À bientôt 👋 (beslema 😄).", 'suggestions' => []];
        }
        if ($this->isGreeting($m)) {
            return [
                'reply' => "Salut 👋😄 Tu veux parler de quoi ? (tu peux parler de tout)",
                'suggestions' => ["Sujet libre", "Produits populaires", "Top prévisions (7 jours)"],
            ];
        }
        if ($this->hasAny($m, ['joke','tell me a joke','blague','nokta','nkt'])) {
            return [
                'reply' => "Pourquoi les programmeurs confondent Halloween et Noël ? Parce que OCT 31 = DEC 25 😄\nTu veux une autre ?",
                'suggestions' => ["Encore une blague", "Sujet libre", "Aide pour la boutique"],
            ];
        }
        return null;
    }

    private function fallbackShop(string $raw): string
    {
        return "Je peux t’aider 🙂 Tu cherches quoi exactement (console, casque, manette, jeux) et ton budget ?";
    }

    /**
     * @return list<string>
     */
    private function shopSuggestions(string $m): array
    {
        if ($this->isGoodbye($m)) return [];
        return ["Produits populaires", "Top prévisions (7 jours)", "Produits faible stock", "Recommander pour produit 1"];
    }

    private function isShopIntent(string $m): bool
    {
        if ($this->isGreeting($m) || $this->isGoodbye($m)) {
            return $this->hasAny($m, ['popular','populaire','forecast','pred','stock','recommend','recomm','budget','prix','product','produit']);
        }

        return $this->hasAny($m, [
            'product','produit','shop','buy','achat','commander','commande','cart','panier',
            'popular','populaire','top','best',
            'forecast','prevision','prévision','pred','prediction','7 jours',
            'stock','rupture',
            'recommend','recomm',
            'budget','price','prix'
        ]);
    }

    private function isGreeting(string $m): bool
    {
        return $this->hasAny($m, ['salem','salam','slm','bonjour','salut','hello','hi','hey']);
    }

    private function isGoodbye(string $m): bool
    {
        return $this->hasAny($m, ['bye','goodbye','au revoir','aurevoir','a bientot','beslema','bslama','ma3a salama']);
    }

    /**
     * @param list<string> $words
     */
    private function hasAny(string $m, array $words): bool
    {
        foreach ($words as $w) {
            if (str_contains($m, mb_strtolower($w))) return true;
        }
        return false;
    }
}