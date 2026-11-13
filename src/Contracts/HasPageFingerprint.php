<?php

namespace InternetGuru\LaravelCommon\Contracts;

/**
 * A model watched on a page (WatchedPage::add()) adds what its updated_at
 * cannot show, such as a state that changes with time alone.
 */
interface HasPageFingerprint
{
    public function pageFingerprint(): string;
}
