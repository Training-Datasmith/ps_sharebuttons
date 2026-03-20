<?php

declare(strict_types=1);

/**
 * Example: Working with the ps_sharebuttons PrestaShop module.
 *
 * ps_sharebuttons renders social sharing buttons (Facebook, Twitter/X,
 * Pinterest, etc.) on product pages, allowing customers to share products
 * on social media with a single click.
 *
 * This file documents common usage patterns.
 */

// --- The module renders on product pages ---
// Hook: displayFooterProduct (below product description)
// PrestaShop dispatches this hook automatically on product detail pages.

// --- Widget invocation in Smarty/Twig template ---
// {widget name="ps_sharebuttons" hook="displayFooterProduct"}

// --- Generating share URLs for a product ---
// The module constructs share URLs using the current product's canonical URL:
//
// $context    = Context::getContext();
// $link       = $context->link;
// $productUrl = $link->getProductLink(
//     product: $product,
//     alias: $product->link_rewrite[$context->language->id],
// );
//
// $encodedUrl = urlencode($productUrl);
// $title      = urlencode($product->name[$context->language->id]);
//
// $facebookUrl  = "https://www.facebook.com/sharer/sharer.php?u=$encodedUrl";
// $twitterUrl   = "https://twitter.com/intent/tweet?url=$encodedUrl&text=$title";
// $pinterestUrl = "https://pinterest.com/pin/create/button/?url=$encodedUrl&description=$title";

// --- Back Office configuration ---
// Modules > Share Buttons:
//   - Enable/disable individual social networks
//   - Facebook, Twitter/X, Pinterest, Google+, LinkedIn

// --- Template override ---
// themes/{theme}/modules/ps_sharebuttons/views/templates/hook/ps_sharebuttons.tpl
