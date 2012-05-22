<?php

namespace Cedriclombardot\OgonePaymentBundle\Propel;

use Cedriclombardot\OgonePaymentBundle\Propel\om\BaseOgoneAlias;


/**
 * Skeleton subclass for representing a row from the 'ogone_alias' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 * @package    propel.generator.src.Cedriclombardot.OgonePaymentBundle.Propel
 */
class OgoneAlias extends BaseOgoneAlias {

    public function toOgone()
    {
        $convertion = array(
           'AliasOperation'=> 'Operation',
           'AliasUsage'    => 'Label',
           'Alias'         => 'Id',
        );

        foreach ($convertion as $ogoneKey => $propelGetter) {
            $convertion[$ogoneKey] = $this->{'get'.$propelGetter}();
        }

        return $convertion;
    }

} // OgoneAlias
