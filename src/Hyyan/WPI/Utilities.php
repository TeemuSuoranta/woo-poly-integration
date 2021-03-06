<?php

/**
 * This file is part of the hyyan/woo-poly-integration plugin.
 * (c) Hyyan Abo Fakher <hyyanaf@gmail.com>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hyyan\WPI;

/**
 * Utilities.
 *
 * Some helper methods
 *
 * @author Hyyan Abo Fakher <hyyanaf@gmail.com>
 */
final class Utilities
{
    /**
     * Get the translations IDS of the given product ID.
     *
     * @global \Polylang $polylang
     *
     * @param int  $ID             the product ID
     * @param bool $excludeDefault true to exclude default language
     *
     * @return array associative array with language code as key and ID of translations
     *               as value
     */
    public static function getProductTranslationsArrayByID($ID, $excludeDefault = false)
    {
        global $polylang;
        $IDS = PLL()->model->post->get_translations($ID);
        if (true === $excludeDefault) {
            unset($IDS[pll_default_language()]);
        }

        return $IDS;
    }

    /**
     * Get the translations IDS of the given product object.
     *
     * @see \Hyyan\WPI\getProductTranslationsByID()
     *
     * @param \WC_Product $product        the product object
     * @param bool        $excludeDefault true to exclude default language
     *
     * @return array associative array with language code as key and ID of translations
     *               as value
     */
    public static function getProductTranslationsArrayByObject(\WC_Product $product, $excludeDefault = false)
    {
        return static::getProductTranslationsArrayByID($product->id, $excludeDefault);
    }

    /**
     * Get product translation by ID.
     *
     * @param int    $ID   the product ID
     * @param string $slug the language slug
     *
     * @return \WC_Product|false product translation if found , false if the
     *                           given ID is not for product
     */
    public static function getProductTranslationByID($ID, $slug = '')
    {
        $product = wc_get_product($ID);
        if (!$product) {
            return false;
        }

        return static::getProductTranslationByObject($product, $slug);
    }

    /**
     * Get product translation by object.
     *
     * @param \WC_Product $product the product to use to retrive translation
     * @param string      $slug    the language slug
     *
     * @return \WC_Product product translation or same product if translaion not found
     */
    public static function getProductTranslationByObject(\WC_Product $product, $slug = '')
    {
        $productTranslationID = pll_get_post($product->id, $slug);

        if ($productTranslationID) {
            $translated = wc_get_product($productTranslationID);
            $product = $translated ? $translated : $product;
        }

        return $product;
    }

    /**
     * Get polylang language entity.
     *
     * @global \Polylang $polylang
     *
     * @param string $slug the language slug
     *
     * @return \PLL_Language|false language entity on success , false otherwise
     */
    public static function getLanguageEntity($slug)
    {
        global $polylang;

        $langs = $polylang->model->get_languages_list();

        foreach ($langs as $lang) {
            if ($lang->slug == $slug) {
                return $lang;
            }
        }

        return false;
    }

    /**
     * Get the translations IDS of the given term ID.
     *
     * @global \Polylang $polylang
     *
     * @param int  $ID             term id
     * @param bool $excludeDefault true to exclude default language
     *
     * @return array associative array with language code as key and ID of translations
     *               as value
     */
    public static function getTermTranslationsArrayByID($ID, $excludeDefault = false)
    {
        global $polylang;
        $IDS = PLL()->model->term->get_translations($ID);
        if (true === $excludeDefault) {
            unset($IDS[pll_default_language()]);
        }

        return $IDS;
    }

    /**
     * Get current url.
     *
     * Get the full url for current location
     *
     * @return string
     */
    public static function getCurrentUrl()
    {
        return (is_ssl() ? 'https://' : 'http://')
                .$_SERVER['HTTP_HOST']
                .$_SERVER['REQUEST_URI'];
    }

    /**
     * Js Script wrapper.
     *
     * Warp the given js code
     *
     * @param string $ID     the script ID
     * @param string $code   js code
     * @param bool   $jquery true to include jQuery ready wrap , false otherwise
     *                       (true by default)
     * @param bool   $return true to return wrapped code , false othwerwise
     *                       false by default
     *
     * @return string wrapped js code if return is true
     */
    public static function jsScriptWrapper($ID, $code, $jquery = true, $return = false)
    {
        $result = '';
        $prefix = 'hyyan-wpi-';
        $header = sprintf('<script type="text/javascript" id="%s">', $prefix.$ID);
        $footer = '</script>';

        if (true === $jquery) {
            $result = sprintf(
                    "%s\n jQuery(document).ready(function ($) {\n %s \n});\n %s \n", $header, $code, $footer
            );
        } else {
            $result = sprintf(
                    "%s\n %s \n%s", $header, $code, $footer
            );
        }

        if (false === $return) {
            echo $result;
        } else {
            return $result;
        }
    }

    /**
     * Check WooCommerce version.
     *
     * Check if you are running a specified WooCommerce version (or higher)
     *
     * @param string $version Version to check agains. (Default: 2.6)
     *
     * @return bool true if running version is equal or higher, false otherwise
     */
    public static function woocommerceVersionCheck($version = '2.6')
    {
        global $woocommerce;

        if (version_compare($woocommerce->version, $version, '>=')) {
            return true;
        }

        return false;
    }
    
    /**
     * Get variations default attributes translation.
     *
     * Get the translation of the default attributes of product passed by id, in
     * a given language, if one is passed, otherwise in all available languages.
     *
     * @param int       $product_id     (required) Product id.
     * @param string    $lang           (optional) Language slug.
     *
     * @return array    Indexed array, with language slug as key, of attributes
     *                  pairs [attribute] => attribute slug
     */
    public static function getDefaultAttributesTranslation($product_id, $lang = '')
    {
        $product = wc_get_product( $product_id );
        $translated_attributes = array();

        if ($product && 'variable' === $product->product_type) {
            $default_attributes = $product->get_variation_default_attributes();
            $terms = array(); // Array of terms: if the term is taxonomy each value is a term object, otherwise an array (term slug => term value)
            $langs = array();

            foreach ($default_attributes as $key => $value) {
                $term = get_term_by('name', $value, $key);

                if ($term && pll_is_translated_taxonomy($term->taxonomy))
                    $terms[] = $term;
                else
                    $terms[] = array($key => $value);
            }

            // For each product translation, get the translated default attributes
            if (empty($lang)) {
                $langs = pll_languages_list();
            } else {
                $langs[] = $lang; // get translation for a specific language
            }

            foreach ($langs as $lang) {
                $translated_terms = array();

                foreach ($terms as $term) {
                    if (is_object($term)) {
                        $translated_term_id = pll_get_term($term->term_id, $lang);
                        $translated_term = get_term_by('id', $translated_term_id, $term->taxonomy);

                        $translated_terms[$translated_term->taxonomy] = $translated_term->slug;
                    } else {
                        $translated_terms[key($term)] = $term[key($term)];
                    }
                }

                $translated_attributes[$lang] = $translated_terms;
            }
        }

        return $translated_attributes;
    }

    /**
     * Check if it product might be a Variable Product.
     *
     * New translations of Variable Products are first created as Simple Products.
     *
     * @param \WC_Product|int   $product    (required) Product object or product id.
     *
     * @return bool true is is variable, false otherwise.
     */
    public static function maybeVariableProduct($product)
    {
        if (is_numeric($product))
            $product = wc_get_product(asbint($product));

        if ($product && 'variable' === $product->product_type)
            return true;
        elseif ($product && 'simple' === $product->product_type) {
            $current_screen  = function_exists('get_current_screen') ? get_current_screen() : false;
            $add_new_product = $current_screen && $current_screen->post_type === 'product' && $current_screen->action === 'add';
            $is_translation  = isset($_GET['from_post']) && isset($_GET['new_lang']);
            $has_variations  = get_children(array(
                    'post_type'   => 'product_variation',
                    'post_parent' => $product->id
                ));

            if ($add_new_product && $is_translation && $has_variations)
                    return true;
        }

        return false;
    }
}
