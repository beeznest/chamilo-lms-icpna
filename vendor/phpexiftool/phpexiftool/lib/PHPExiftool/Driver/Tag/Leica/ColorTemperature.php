<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Leica;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ColorTemperature extends AbstractTag
{

    protected $Id = 801;

    protected $Name = 'ColorTemperature';

    protected $FullName = 'Panasonic::Leica2';

    protected $GroupName = 'Leica';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Leica';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Color Temperature';

    protected $flag_Permanent = true;

}
