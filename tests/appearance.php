<?php
/** Standalone tokenizer/scoping regression: php tests/appearance.php */
define('ABSPATH', __DIR__);
require __DIR__ . '/../src/Appearance/CssCompiler.php';
use FandooghRest\Appearance\CssCompiler;
$checks = 0;
function check(bool $condition, string $label): void { global $checks; if (!$condition) { throw new RuntimeException($label); } $checks++; }
$scope = '.ac-appearance-preview';
$compiler = new CssCompiler();
$css = $compiler->compile('.ac-menu:hover {color:red} .ac-product, button:hover {padding:calc(2px + 1vw);content:"a;b, c"} @media screen and (min-width:700px) {@supports (display:grid) {.ac-product {display:grid}}}', $scope);
check(str_contains($css, $scope . ':hover{color:red;}'), 'root selector mapping');
check(str_contains($css, $scope . ' .ac-product,' . $scope . ' button:hover'), 'each comma-list member scoped');
check(str_contains($css, '@media screen and (min-width:700px){@supports (display:grid){' . $scope), 'responsive scoped nesting');
check($compiler->compile('content:"a;b}";color:/* valid */red', $scope, true) === 'content:"a;b}";color:red;', 'strings and comments preserved safely');
$bad = ['body{color:red}', ':root{color:red}', '.ac-menu ~ .outside{color:red}', ':hover + p{color:red}', '@import "https://bad.test";', '@font-face {font-family:test}', '@keyframes test{from{opacity:0}}', 'a{background:url(https://bad.test)}', 'a{background:image-set("https://bad.test" 1x)}', 'a{color:exp/**/ression(alert(1))}', 'a{content:attr(data-secret url)}', 'a{background:var(--inherited)}', 'a{-webkit-border-image:var(--external)}', 'a{fill:var(--external)}', 'a{-webkit-box-reflect:var(--external)}', 'a{--bad:url(x)}', 'a{behavior:foo}', 'a{color:r\\65 d}', 'a{content:"</style>"}', 'a{color:red; b{color:blue}}', 'a{color:calc(1px;color:red)}', 'a{color:rgb(1,2;3}', 'a{color:red} }', '/* unclosed', 'a{content:"unclosed}', 'a{color:red'];
foreach ($bad as $input) {
    try { $compiler->compile($input, $scope); throw new RuntimeException('Accepted unsafe or malformed CSS: ' . $input); }
    catch (InvalidArgumentException) { $checks++; }
}
foreach (['color:red}body{color:red', 'color:red;@import "x"', 'a{color:red}', 'color:expression(1)'] as $input) {
    try { $compiler->compile($input, $scope, true); throw new RuntimeException('Accepted unsafe declarations: ' . $input); }
    catch (InvalidArgumentException) { $checks++; }
}
echo "Appearance parser: $checks checks passed.\n";
