<?php
// Block direct access to the main plugin file.
defined( 'ABSPATH' ) || die( 'Access Denied!' );

/**
 * Hanlde Houzez Taxonomy Data
 * 
 * @author Chris A <chris.a@realtyna.net>
 * 
 * @version 1.0
 */
class RealtynaHouzezTaxonomy {

    /** @var string taxonomy value */
    const TAXONOMY = '';

    /**
     * Add Taxonomy to Post
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param string Term Title
     * @param int Post ID
     * @param array array of Parent info
     * 
     * @return bool
     */
    public function import( $term , $postId , $parentInfo = array() ){

        if ( !empty( trim( $term ) ) || !empty( trim( static::TAXONOMY ) ) ){

            $termId = $this->addTerm( $term );

            if ( $termId > 0 ){
                
                if ( isset( $parentInfo['metaKey'] ) && isset( $parentInfo['parentKey'] ) && isset( $parentInfo['parentValue'] ) ){

                    $this->addParentOption( $termId , $parentInfo['metaKey'] , $parentInfo['parentKey'] , $parentInfo['parentValue'] );
                    
                }                

                $taxId = $this->addTax( $termId );

                if ( $taxId > 0 )

                    return ( $this->addPostRelation( $postId , $taxId ) );

            }

        }

        return false;

    }

    /**
     * Add relation between taxonomy and post
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param int Post ID
     * @param int Taxonomy ID
     * 
     * @return bool
     */
    private function addPostRelation( $postId , $taxId ){

        global $wpdb;

        if ( $postId > 0 && $taxId > 0 ){

            $counts = $this->existsRelation( $postId , $taxId );

            if ( $counts > 0 )
                return true ;

            $tableRelation = $wpdb->prefix.'term_relationships';

            $relationData = array( 'object_id' => $postId , 'term_taxonomy_id' => $taxId );

            $wpdb->insert( $tableRelation , $relationData );
    
            return true;    

        }

        return false;

    }

    /**
     * Add New Taxonomy based on term if not exists
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param int Term ID
     * 
     * @return int Taxonomy ID
     */
    private function addTax( $termId ){

        global $wpdb;

        $taxId = $this->existsTax( $termId );

        if ( !is_wp_error( $taxId ) && $taxId > 0 )
            return $taxId ;

        $tableTax = $wpdb->prefix.'term_taxonomy';
        
        $taxData = array('term_id' => $termId , 'taxonomy' => static::TAXONOMY );

        $wpdb->insert( $tableTax , $taxData );

        return $wpdb->insert_id;

    }

    /**
     * Add New Term if not exists
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param string Term Title
     * 
     * @return int Term ID
     */
    private function addTerm( $term ){

        global $wpdb;

        $termId = $this->existsTerm( $term );

        if ( !is_wp_error( $termId ) && $termId > 0 )
            return $termId ;

        $tableTerm = $wpdb->prefix.'terms';
        
        $termData = array('name' => addslashes( $term ) , 'slug' => $this->createSlug( $term ) );

        $wpdb->insert( $tableTerm , $termData );

        return $wpdb->insert_id;

    }

    /**
     * Check existance of taxonomy with post
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param int Post ID
     * @param int Taxonomy ID
     * 
     * @return int Total Taxonomy with same ID , Zero means Not exists
     */
    private function existsRelation( $postId , $taxId ){

        global $wpdb;

        $tableRelation = $wpdb->prefix.'term_relationships';

        return $wpdb->get_var(
                        "
                            SELECT count(1)
                            FROM $tableRelation
                            WHERE  term_taxonomy_id = $taxId AND object_id = $postId
                        "
                    );

    }   

    /**
     * Check Taxonomy existance with Term ID
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param int Term ID
     * 
     * @return int return term_taxonomy_id value
     */
    private function existsTax( $termId ){

        global $wpdb;

        $tableTax = $wpdb->prefix.'term_taxonomy';

        return $wpdb->get_var(
                        "
                            SELECT term_taxonomy_id
                            FROM $tableTax
                            WHERE  taxonomy = '" . static::TAXONOMY . "' AND term_id = " . $termId
                        );

    }   

    /**
     * Check Term Exitance with Term title
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param string Term title
     * 
     * @return int term_id
     */
    private function existsTerm( $term ){

        global $wpdb;

        $tableTerm = $wpdb->prefix.'terms';
        $tableTax = $wpdb->prefix.'term_taxonomy';

        return $wpdb->get_var(
                        "
                            SELECT $tableTerm.term_id
                            FROM $tableTerm
                            JOIN $tableTax ON $tableTerm.term_id = $tableTax.term_id
                            WHERE  $tableTax.taxonomy = '" . static::TAXONOMY . "' AND $tableTerm.name = '" . addslashes( $term ) ."'"
                        );

    }

    /**
     * Create Slug For a title or string
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param string 
     * 
     * @return string converted string to slug
     */
    private function createSlug( $title ){

        if ( !empty( trim( $title ) ) ){

            $title = preg_replace('/[\!\(\)\$\&\*\_\#\@\.\;\'\"]+/', '', $title);
            return str_replace( ' ' , '-' , strtolower( trim ( $title ) ) );

        }

        return '';

    }

    /**
     * Create Post Option to store Parent for each term
     * 
     * @author Chris A <chris.a@realtyna.net>
     * 
     * @param int Term ID
     * @param string MataKey For Parent 
     * @param string parent Key
     * @param string parent Value
     * 
     * @return bool
     */
    private function addParentOption( $termID , $metaKey , $parentKey , $parentValue) {

        if ( function_exists('update_option') ){

            if ( !empty( $termID ) && !empty( $metaKey ) && !empty( $parentKey ) && !empty( $parentValue ) ){

                $metaKey .= "_$termID" ;

                $metaValue = array( 
                    $parentKey => $this->createSlug( $parentValue )
                );
    
                return update_option( $metaKey , $metaValue );
    
            }
    
        }

        return false;

    }

}
?>