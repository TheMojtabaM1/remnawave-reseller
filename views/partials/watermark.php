<?php
use App\Core\Auth;
// #164 — faint, traceable watermark (user + date) tiled across the page so a
// leaked screenshot can be traced back to the account that took it.
$label = trim((string) Auth::name() . '  ' . shamsi(now_utc(), 'date'));
$svg = "<svg xmlns='http://www.w3.org/2000/svg' width='320' height='170'>"
     . "<text x='20' y='90' fill='#ffffff' fill-opacity='0.5' font-family='Arad,sans-serif' font-size='13' "
     . "transform='rotate(-28 160 85)'>" . htmlspecialchars($label, ENT_QUOTES) . "</text></svg>";
$uri = 'data:image/svg+xml;base64,' . base64_encode($svg);
?>
<div aria-hidden="true" style="position:fixed;inset:0;z-index:60;pointer-events:none;opacity:.05;
  background-image:url('<?= $uri ?>');background-repeat:repeat;mix-blend-mode:overlay;"></div>
