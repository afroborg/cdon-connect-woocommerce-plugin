<?php

class CDON_Feed
{
  private $_products;
  private $_feed_name;
  private $_markets;
  private $_remove;
  private $_logger;

  private const SCHEMA_VERSION = '4.8.0';
  private const SCHEMA_URL = 'https://schemas.cdon.com/product/4.0/' . self::SCHEMA_VERSION . '/';

  public function __construct(string $feed_name, $get_all = false, $remove = false)
  {
    $query = new WC_Product_Query();

    $query->set('downloadable', false);
    $query->set('virtual', false);
    // $query->set('type', 'simple');

    if (!$get_all) {
      $query->set('cdon_export_' . $feed_name, 'yes');
    }

    $this->_products = $query->get_products();

    $this->_feed_name = $feed_name;

    $this->_markets = array_filter(array_keys(MARKETS), function ($market) {
      return get_option('cdon_' . $market . '_enabled') == 'yes';
    });

    $this->_remove = $remove;

    $this->_logger = new WC_Logger();
  }

  public function create_feed(): string
  {
    if (count($this->_markets) > 0) {
      $feed = $this->{'_' . $this->_feed_name}();

      if ($this->_remove) {
        $this->_remove_from_next_export();
      }

      return $feed;
    } else {
      return '<error><message>No markets enabled</message></error>';
    }
  }

  private function _product(): string
  {

    [$xml, $marketplace] = $this->_newXmlDocument('product');

    foreach ($this->_products as $key => $product) {

      if ($product->get_type() == 'simple') {
        $product_element = $xml->createElement('product');

        // Set the identity of the product
        $identity_element = $xml->createElement('identity');
        $id_element = $xml->createElement('id', $this->_get_attribute_from_option($product, 'cdon_product_id', 'id'));
        $identity_element->appendChild($id_element);

        $gtin = $this->_get_attribute_from_option($product, 'cdon_gtin', '') ?? '';
        $gtin_length = strlen($gtin);

        // GTIN must be either 8, 12, 13 or 14 characters
        if ($gtin && ($gtin_length == 8 || ($gtin_length > 11 && $gtin_length < 15))) {
          $gtin_element = $xml->createElement('gtin', $gtin);
          $identity_element->appendChild($gtin_element);
        }

        $sku = $this->_get_attribute_from_option($product, 'cdon_sku', '');

        if ($sku) {
          $sku_element = $xml->createElement('sku', $sku);
          $identity_element->appendChild($sku_element);
        }

        $product_element->appendChild($identity_element);

        // Generate title(s)
        $title_element = $xml->createElement('title');
        $title = $product->get_title();
        $default_title_element = $xml->createElement('default');
        $default_title_element->appendChild($xml->createCDATASection($title));
        $title_element->appendChild($default_title_element);

        foreach ($this->_markets as $market) {
          $market_title = $product->get_data()[get_option('cdon__title_market--' . $market)] ?: $product->get_title();
          $market_element = $xml->createElement($market);
          $market_element->appendChild($xml->createCDATASection($market_title));
          $title_element->appendChild($market_element);
        }

        $product_element->appendChild($title_element);

        // Generate descriptions
        $description_element = $xml->createElement('description');
        $description = $product->get_description() != '' ?: ($product->get_short_description() != '' ?: 'N/A');
        $default_description_element = $xml->createElement('default');
        $default_description_element->appendChild($xml->createCDATASection($description));
        $description_element->appendChild($default_description_element);

        foreach ($this->_markets as $market) {
          $market_description =  $this->_get_attribute_from_option($product, 'cdon__description_market--' . $market, 'description');

          if (!$market_description)
            continue;

          $market_element = $xml->createElement($market);
          $market_element->appendChild($xml->createCDATASection($market_description));
          $description_element->appendChild($market_element);
        }

        $product_element->appendChild($description_element);

        // Get category from mapped categories
        $category_id = ($product->get_category_ids())[0];
        $category = get_option('cdon_category_' . $category_id);

        if (!$category) {
          $this->_logger->log('cdon_skipped', 'Product #' . $product->get_id() . ' was skipped because of unmapped category');
          continue;
        }

        $category_root_element = $xml->createElement('category');
        $category_element = $xml->createElement($category);
        $category_root_element->appendChild($category_element);
        $product_element->appendChild($category_root_element);

        // Set dimensions
        $dimensions_element = $xml->createElement('dimensions');
        $dimensions = [
          'height' => $product->get_height(),
          'width' => $product->get_width(),
          'length' => $product->get_length(),
          'weight' => $product->get_weight(),
        ];

        foreach ($dimensions as $key => $dimension) {

          if ($dimension) {
            $unit = get_option($key == 'weight' ? 'woocommerce_weight_unit' : 'woocommerce_dimension_unit', 'cm');
            $dimensions_element->appendChild($this->_dimensionElement($xml, $key, $dimension, $unit));
          }
        };

        $product_element->appendChild($dimensions_element);

        // Optional elements
        $product_element->appendChild($xml->createElement('isAdult', 'false'));
        $product_element->appendChild($xml->createElement('isDrug', 'false'));
        $product_element->appendChild($xml->createElement('isPreOwned', 'false'));

        $marketplace->appendChild($product_element);
      }
    };

    return $this->_return($xml);
  }

  private function _price(): string
  {
    [$xml, $marketplace] = $this->_newXmlDocument('price');

    foreach ($this->_products as $product) {
      $product_element = $xml->createElement('product');

      // Set the identity of the product
      $id_element = $xml->createElement('id', $this->_get_attribute_from_option($product, 'cdon__product_id', 'id'));
      $product_element->appendChild($id_element);

      $has_complete_market = false;
      foreach ($this->_markets as $market) {
        $market_element = $xml->createElement($market);
        $original_price = ($this->_get_attribute_from_option($product, 'cdon_' . $market . '_sales_price') ?: $product->get_regular_price()) ?: $product->get_price();
        if (!$original_price)
          continue;
        $sale_price = ($this->_get_attribute_from_option($product, 'cdon_' . $market . '_sales_price') ?: $product->get_sale_price()) ?: $original_price;
        $sale_price_element = $xml->createElement('salePrice', $sale_price);
        $original_price_element = $xml->createElement('originalPrice', $original_price);
        $shipping_cost_element = $xml->createElement('shippingCost', $product->get_shipping_class() ?? '0');
        $vat_element = $xml->createElement('vat', $product->get_tax_class ?? '25');

        $market_element->appendChild($sale_price_element);
        $market_element->appendChild($original_price_element);
        $market_element->appendChild($shipping_cost_element);
        $market_element->appendChild($vat_element);
        $product_element->appendChild($market_element);
        $has_complete_market = true;
      }
      if (!$has_complete_market)
        continue;

      $marketplace->appendChild($product_element);
    }

    return $this->_return($xml);
  }

  private function _availability(): string
  {
    [$xml, $marketplace] = $this->_newXmlDocument('availability');

    foreach ($this->_products as $product) {
      $product_element = $xml->createElement('product');

      // Set the identity of the product
      $id_element = $xml->createElement('id', $this->_get_attribute_from_option($product, 'cdon__product_id', 'id'));
      $product_element->appendChild($id_element);

      $stockElement = $xml->createElement('stock', $product->get_stock_quantity() ?? 0);
      $product_element->appendChild($stockElement);

      foreach ($this->_markets as $market) {
        $market_element = $xml->createElement($market);
        $status = $xml->createElement('status', $product->get_status() == 'publish' && $product->get_stock_status() == 'instock' ? 'Online' : 'Offline');
        $delivery_time = $xml->createElement('deliveryTime');
        $delivery_time_min = $xml->createElement('min', get_option('cdon__delivery_time--min', 1));
        $delivery_time_max = $xml->createElement('max', get_option('cdon__delivery_time--max', 3));
        $delivery_time->appendChild($delivery_time_min);
        $delivery_time->appendChild($delivery_time_max);

        $market_element->appendChild($status);
        $market_element->appendChild($delivery_time);
        $product_element->appendChild($market_element);
      }

      $marketplace->appendChild($product_element);
    }

    return $this->_return($xml);
  }

  private function _media(): string
  {
    [$xml, $marketplace] = $this->_newXmlDocument('media');

    foreach ($this->_products as $product) {
      $product_element = $xml->createElement('product');

      // Set the identity of the product
      $id_element = $xml->createElement('id', $this->_get_attribute_from_option($product, 'cdon__product_id', 'id'));
      $product_element->appendChild($id_element);

      $images_element = $xml->createElement('images');
      $main_image_id =  $product->get_image_id();

      if (!$main_image_id)
        continue;

      $main_image_element = $xml->createElement('main', wp_get_attachment_image_url($main_image_id, 'full'));
      $images_element->appendChild($main_image_element);

      foreach ($product->get_gallery_image_ids() as $i => $imageId) {
        if ($i > 9)
          break;

        $url = wp_get_attachment_image_url($imageId, 'full');
        $extra_image_element = $xml->createElement('extra', $url);
        $images_element->appendChild($extra_image_element);
      }

      $product_element->appendChild($images_element);

      $marketplace->appendChild($product_element);
    }

    return $this->_return($xml);
  }

  private function _return(DomDocument $xml): string
  {
    return $xml->saveXML();
  }

  private function _get_attribute_from_option($product, $option_name, $fallback = null): ?string
  {
    $option = get_option($option_name, $fallback);
    if ($option) {
      try {
        return $product->get_data()[$option];
      } catch (Exception $exception) {
      }
    }
    return null;
  }

  private function _newXmlDocument(string $endpoint)
  {
    $xml = new DOMDocument('1.0', 'UTF-8');
    $xml->formatOutput = true;

    $comment = $xml->createComment($this->_comment($endpoint));
    $xml->appendChild($comment);

    $marketplace = $xml->createElement('marketplace');
    $marketplace->setAttribute('xmlns', self::SCHEMA_URL . $endpoint);
    $xml->appendChild($marketplace);

    return [$xml, $marketplace];
  }

  private function _comment(string $endpoint): string
  {
    return 'CDON ' . $endpoint . ' feed created automatically at ' . date('Y-m-d H:i:s') . ' by CDON Connect Woocommerce plugin';
  }

  private function _dimensionElement(DomDocument $xml, string $dimension_name, string $value, string $unit): DOMElement
  {
    $element = $xml->createElement($dimension_name);
    $value = $xml->createElement('value', $value);
    $unit = $xml->createElement('unit', $unit);
    $element->appendChild($value);
    $element->appendChild($unit);

    return $element;
  }

  private function _remove_from_next_export(): void
  {
    foreach ($this->_products as $product) {
      update_post_meta($product->id, 'cdon_export_' . $this->_feed_name, 'no');
    }
  }
}
