<?php

namespace App\Tests\Service;

use App\Entity\Guide;
use App\Service\GuideManager;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class GuideManagerTest extends TestCase
{
    // TEST 1: A valid guide with general data
    public function testValidGuide()
    {
        $guide = new Guide();
        $guide->setTitle('General Test Guide Title');
        $guide->setDescription('This is a valid general description that exceeds ten characters.');

        $manager = new GuideManager();
        $this->assertTrue($manager->validate($guide));
    }

    // TEST 2: The title is missing
    public function testGuideWithoutTitle()
    {
        $this->expectException(InvalidArgumentException::class);

        $guide = new Guide();
        $guide->setDescription('This is a valid general description that exceeds ten characters.');

        $manager = new GuideManager();
        $manager->validate($guide);
    }

    // TEST 3: The description is too short
    public function testGuideWithShortDescription()
    {
        $this->expectException(InvalidArgumentException::class);

        $guide = new Guide();
        $guide->setTitle('General Test Guide Title');
        $guide->setDescription('Too short'); // Only 9 characters!

        $manager = new GuideManager();
        $manager->validate($guide);
    }
}