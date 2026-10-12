<?php

namespace Tests\Unit\Services\Social;

use App\Services\Social\TwitterPublisher;
use PHPUnit\Framework\TestCase;

class TwitterPublisherTest extends TestCase
{
    private const LONG_POST = "Every headline starts a conversation. Let’s make this your place to join it.\n\nWelcome to Daily US Wire! 👋\n\nFrom technology changing how we live to business, health, sports and entertainment, we explore the stories shaping our world—and the questions behind them.\n\nExpect news, explainers and practical reads across the topics you care about.\n\nFollow our page, explore the website and join the conversation from our very first post.\n\n💬 What would you like to see more of: technology, business, health or sports?\n\nExplore → [dailyuswire.com](https://dailyuswire.com/)\n\n#DailyUSWire #USNews #Technology #Business #StayInformed";

    public function test_short_text_is_returned_unchanged(): void
    {
        $text = 'Hello from PostPilot 👋 https://example.com';

        $this->assertSame($text, TwitterPublisher::fit($text));
    }

    public function test_long_text_is_shortened_to_at_most_280_weighted_characters(): void
    {
        $fitted = TwitterPublisher::fit(self::LONG_POST);

        $this->assertLessThanOrEqual(280, TwitterPublisher::weightedLength($fitted));
        $this->assertStringEndsWith('…', $fitted);
        $this->assertStringStartsWith('Every headline starts a conversation.', $fitted);
    }

    public function test_ellipsis_and_emoji_count_as_two_characters(): void
    {
        $this->assertSame(2, TwitterPublisher::weightedLength('…'));
        $this->assertSame(2, TwitterPublisher::weightedLength('👋'));
        $this->assertSame(3, TwitterPublisher::weightedLength('a—b'));
    }

    public function test_links_count_as_23_characters_whatever_their_length(): void
    {
        $this->assertSame(27, TwitterPublisher::weightedLength('See https://example.com/a/very/long/path/that/goes/on/and/on'));
        $this->assertSame(23, TwitterPublisher::weightedLength('dailyuswire.com'));
        $this->assertSame(23, TwitterPublisher::weightedLength('www.example.org/page'));
    }

    public function test_email_addresses_are_not_counted_as_links(): void
    {
        $this->assertSame(14, TwitterPublisher::weightedLength('user@gmail.com'));
    }

    public function test_shortening_never_cuts_a_link_in_half(): void
    {
        $text = str_repeat('a', 270).' https://example.com/path';

        $fitted = TwitterPublisher::fit($text);

        $this->assertStringNotContainsString('https://', $fitted);
        $this->assertSame(str_repeat('a', 270).'…', $fitted);
    }
}
