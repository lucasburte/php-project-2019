<?php


namespace mywishlist\views;

class AccueilView
{

    public function render() {
        $body = <<<BODY
<div id="content">
    <div id="content-inner">
    
         <p><h1>TO-DO : Page d'accueil</h1></p>
        
        <div class="clr"></div>
    </div>
</div>
BODY;
        ViewRendering::render($body);
    }
}