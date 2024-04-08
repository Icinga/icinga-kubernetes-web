<?php

namespace Icinga\Module\Kubernetes\SVG;

/**
 * Provide a set of colors that will be used by the chart as default values
 */
class Palette
{
    /**
     * Neutral colors without special meaning
     */
    public const NEUTRAL = 'neutral';

    /**
     * A set of ok (i.e. green) colors
     */
    public const OK = 'ok';

    /**
     * A set of warning (i.e. yellow) colors
     */
    public const WARNING = 'warning';

    /**
     * A set of problem (i.e. red) colors
     */
    public const CRITICAL = 'critical';

    /**
     * A set of pending (i.e. blue) colors
     */
    public const PENDING = 'pending';

    /**
     * A set of unknown (i.e. purple) colors
     */
    public const UNKNOWN = 'unknown';

    /**
     * The color sets for specific categories
     *
     * @var array
     */
    public $colorSets = array(
        self::NEUTRAL  => array('#5c5c5c'),
        self::OK       => array('#44bb77'),
        self::WARNING  => array('#ffaa44'),
        self::CRITICAL => array('#ff5566'),
        self::PENDING  => array('#77aaff'),
        self::UNKNOWN  => array('#aa44ff')
    );

    /**
     * Return the next available color as a hex string for the given type
     *
     * @param string $type The type to receive a color from
     *
     * @return  string          The color in hex format
     */
    public function getNext($type = self::NEUTRAL)
    {
        if (! isset($this->colorSets[$type])) {
            $type = self::NEUTRAL;
        }

        $color = current($this->colorSets[$type]);
        if ($color === false) {
            reset($this->colorSets[$type]);

            $color = current($this->colorSets[$type]);
        }
        next($this->colorSets[$type]);
        return $color;
    }
}
