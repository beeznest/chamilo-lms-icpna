<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\RealRA3;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Copyright extends AbstractTag
{

    protected $Id = 9;

    protected $Name = 'Copyright';

    protected $FullName = 'Real::AudioV3';

    protected $GroupName = 'Real-RA3';

    protected $g0 = 'Real';

    protected $g1 = 'Real-RA3';

    protected $g2 = 'Audio';

    protected $Type = 'string';

    protected $Writable = false;

    protected $Description = 'Copyright';

    protected $local_g2 = 'Author';

}
