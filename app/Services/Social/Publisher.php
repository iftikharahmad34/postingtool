<?php

namespace App\Services\Social;

abstract class Publisher
{
    public function __construct(protected array $c)
    {
    }

    /**
     * @param  array{path:string, mime:string, name:string, url:?string}|null  $image
     * @return array{id:string, url:string, note?:string}
     */
    abstract public function publish(string $text, ?array $image): array;

    /** Verify the credentials. Returns the account name, throws PublishException on failure. */
    abstract public function verify(): string;

    protected function cred(string $key): string
    {
        return trim((string) ($this->c[$key] ?? ''));
    }
}
