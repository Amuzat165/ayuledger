<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;

/**
 * Color Helper
 * 
 * Provides utilities for company color management across the application
 * The company color is stored in settings and dynamically overrides Material Design cyan (#1ABC9C, #00BCD4)
 */
class ColorHelper
{
    /**
     * Get the company color from settings
     * 
     * @return string Hex color code (e.g., '#00897B')
     */
    public static function getCompanyColor()
    {
        return Config::get('settings.company_color', '#00897B');
    }

    /**
     * Get company color as RGB
     * 
     * @return array ['r' => int, 'g' => int, 'b' => int]
     */
    public static function getCompanyColorRGB()
    {
        $color = self::getCompanyColor();
        $hex = ltrim($color, '#');
        
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 1, 2)),
            'b' => hexdec(substr($hex, 2, 2)),
        ];
    }

    /**
     * Get company color as RGBA
     * 
     * @param float $alpha Opacity (0-1)
     * @return string RGBA color (e.g., 'rgba(0, 137, 123, 0.5)')
     */
    public static function getCompanyColorRGBA($alpha = 1.0)
    {
        $rgb = self::getCompanyColorRGB();
        return "rgba({$rgb['r']}, {$rgb['g']}, {$rgb['b']}, {$alpha})";
    }

    /**
     * Get darker shade of company color
     * 
     * @param float $percent Percentage to darken (0-1, where 0.1 = 10% darker)
     * @return string Hex color code
     */
    public static function getDarkerCompanyColor($percent = 0.1)
    {
        $rgb = self::getCompanyColorRGB();
        
        $r = max(0, (int)($rgb['r'] * (1 - $percent)));
        $g = max(0, (int)($rgb['g'] * (1 - $percent)));
        $b = max(0, (int)($rgb['b'] * (1 - $percent)));
        
        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) .
                     str_pad(dechex($g), 2, '0', STR_PAD_LEFT) .
                     str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Get lighter shade of company color
     * 
     * @param float $percent Percentage to lighten (0-1, where 0.1 = 10% lighter)
     * @return string Hex color code
     */
    public static function getLighterCompanyColor($percent = 0.1)
    {
        $rgb = self::getCompanyColorRGB();
        
        $r = min(255, (int)($rgb['r'] * (1 + $percent)));
        $g = min(255, (int)($rgb['g'] * (1 + $percent)));
        $b = min(255, (int)($rgb['b'] * (1 + $percent)));
        
        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) .
                     str_pad(dechex($g), 2, '0', STR_PAD_LEFT) .
                     str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Check if color is light or dark
     * 
     * @return bool true if light, false if dark
     */
    public static function isLightColor()
    {
        $rgb = self::getCompanyColorRGB();
        $brightness = ($rgb['r'] * 299 + $rgb['g'] * 587 + $rgb['b'] * 114) / 1000;
        return $brightness > 128;
    }

    /**
     * Get optimal text color (black or white) based on background color
     * 
     * @return string 'black' or 'white'
     */
    public static function getOptimalTextColor()
    {
        return self::isLightColor() ? '#000000' : '#ffffff';
    }

    /**
     * Get inline style for company color background
     * 
     * @return string CSS style attribute value
     */
    public static function getColorStyle()
    {
        return "style=\"background-color: " . self::getCompanyColor() . "; color: " . self::getOptimalTextColor() . ";\"";
    }
}
