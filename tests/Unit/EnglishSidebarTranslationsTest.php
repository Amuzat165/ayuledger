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

    public function test_english_reports_hub_translations_are_available_without_fallback()
    {
        $langPath = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'lang';
        $translator = new Translator(new FileLoader(new Filesystem(), $langPath), 'main');
        $translator->setFallback('missing');

        $this->assertSame('REPORTING CENTRE', $translator->get('sections.reports_hub.eyebrow'));
        $this->assertSame('Reports', $translator->get('sections.reports_hub.title'));
        $this->assertSame(
            'Explore financial, accounting, inventory, and operational reports.',
            $translator->get('sections.reports_hub.description')
        );
    }
}
