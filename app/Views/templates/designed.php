<?php
/** Renders a design made in the admin template designer. Callers pass $design (see design_of()). */
if (empty($design)) { return; }
$lang = \App\Libraries\Region::languages()[service('request')->getLocale()] ?? [];
echo \App\Libraries\Design\DesignRenderer::fontLinks($design, $lang);
echo \App\Libraries\Design\DesignRenderer::websiteHtml($design, $fields, $content, $lang);
