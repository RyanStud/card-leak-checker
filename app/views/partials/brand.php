<?php
$brandLogoIcon = base_path('/public/assets/images/logo.png');
$brandLogoText = base_path('/public/assets/images/logoescrita.png');
?>
<div style="display:flex; align-items:center; gap:12px; margin:0 0 16px;">
    <img src="<?= e($brandLogoIcon) ?>" alt="Logo Card Leak Checker" style="width:56px; height:56px; object-fit:contain;">
    <img src="<?= e($brandLogoText) ?>" alt="Card Leak Checker - nome e slogan" style="height:36px; width:auto; object-fit:contain;">
</div>
