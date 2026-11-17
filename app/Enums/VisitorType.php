<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum VisitorType: string implements HasLabel
{
    // shortcut: Filament selects follow declaration order, keep new cases alphabetical by label.
    case ALTITUDE = 'altitude';
    case AMPLIFY = 'amplify';
    case BALIONAIRE = 'balionaire';
    case CHAMPION = 'champion';
    case DC_AND_AMBASSADOR = 'dc and ambassador';
    case DIGNIFY = 'dignify';
    case FIREFLY = 'firefly';
    case GLORIFY = 'glorify';
    case GROW = 'grow';
    case GUEST = 'guest';
    case INTERNATIONAL_MEMBER = 'international member';
    case MAGNIFY = 'magnify';
    case MAGNITUDE = 'magnitude';
    case MAHARDIKA = 'mahardika';
    case MULTIPLY = 'multiply';
    case MULTIRICH = 'multirich';
    case NATIONAL_DIRECTOR = 'national director';
    case NUSANTARA = 'nusantara';
    case OBSERVER = 'observer';
    case OTHER = 'other';
    case PASSION = 'passion';
    case PIONEER = 'pioneer';
    case RISE = 'rise';
    case STAR = 'star';
    case VICTORY = 'victory';
    case VISION = 'vision';
    case VISITOR = 'visitor';
    case VISITOR_HOST = 'visitor Host';

    /**
     * Get the label for the given enum value.
     */
    public function getLabel(): ?string
    {
        return match ($this) {
            self::VISITOR => 'Visitor',
            self::INTERNATIONAL_MEMBER => 'International Member',
            self::NATIONAL_DIRECTOR => 'National Director',
            self::DC_AND_AMBASSADOR => 'DC & Ambassador',
            self::ALTITUDE => 'Altitude',
            self::MAGNITUDE => 'Magnitude',
            self::MAGNIFY => 'Magnify',
            self::DIGNIFY => 'Dignify',
            self::FIREFLY => 'Firefly',
            self::AMPLIFY => 'Amplify',
            self::GLORIFY => 'Glorify',
            self::BALIONAIRE => 'Balionaire',
            self::RISE => 'Rise',
            self::MULTIPLY => 'Multiply',
            self::MAHARDIKA => 'Mahardika',
            self::NUSANTARA => 'Nusantara',
            self::VICTORY => 'Victory',
            self::GROW => 'Grow',
            self::VISION => 'Vision',
            self::CHAMPION => 'Champion',
            self::PASSION => 'Passion',
            self::STAR => 'Star',
            self::PIONEER => 'Pioneer',
            self::MULTIRICH => 'Multirich',
            self::OTHER => 'Other Chapter / Core Group',
            self::OBSERVER => 'Observer',
            self::VISITOR_HOST => 'Visitor Host',
            self::GUEST => 'Guest',
        };
    }

    public function getBgImgPath(): string
    {
        return match ($this) {
            self::VISITOR => asset('img/zoom-bg/Visitor.png'),
            self::NATIONAL_DIRECTOR => asset('img/zoom-bg/National Director.png'),
            self::ALTITUDE,
            self::MAGNITUDE => asset('img/zoom-bg/Member.png'),

            // TODO: WE REALLY SHOULD PUT THIS LINK IN A CONSTANT SOMEWHERE, NOT HARD CODED.
            default => 'https://drive.google.com/drive/folders/1N7GMUHap1w-J29MdaXMWi8p76revHFEq?usp=sharing',
        };
    }
}
