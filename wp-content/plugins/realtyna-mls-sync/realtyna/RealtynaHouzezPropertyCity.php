<?php
// Block direct access to the main plugin file.
defined( 'ABSPATH' ) || die( 'Access Denied!' );

/**
 * Hanlde New Taxonomy for Houzez City
 * 
 * @author Chris A <chris.a@realtyna.net>
 * 
 * @version 1.0
 */
class RealtynaHouzezPropertyCity extends RealtynaHouzezTaxonomy {

    /** @var string taxonomy value */
    const TAXONOMY = 'property_city';

}