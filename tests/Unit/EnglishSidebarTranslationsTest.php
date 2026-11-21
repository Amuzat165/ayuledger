<?php

namespace Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;

class EnglishSidebarTranslationsTest extends \PHPUnit\Framework\TestCase
{
    public function test_legacy_english_locale_resolves_sidebar_titles_without_fallback()
    {
        $langPath = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'lang';
        $translator = new Translator(new FileLoader(new Filesystem(), $langPath), 'eng');
        $translator->setFallback('missing');

        $this->assertSame('Financial Workspace', $translator->get('financial.screens.financial_workspace'));
        $this->assertSame('Workspace', $translator->get('financial.screens.workspace'));
    }
}
