<?php

namespace App\Notifications\Messages;

/**
 * Simple value object describing an outgoing SMS.
 */
class SmsMessage
{
    public function __construct(
        public string $content = '',
        public ?string $from = null,
    ) {
    }

    public static function create(string $content): self
    {
        return new self($content);
    }

    public function content(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function from(string $from): self
    {
        $this->from = $from;

        return $this;
    }
}
