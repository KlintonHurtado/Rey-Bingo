<?php
/**
 * Vista de Modo Espectador de Rey Bingo
 * 
 * Redirige la renderización al motor maestro de sala de juego (playings/playing)
 * garantizando apariencia idéntica 1:1, balotas con animación en vivo, audio de números
 * y locución en español sin dependencias rotas.
 */
$viewData = array_merge(get_defined_vars(), ['isSpectator' => true]);
echo view('playings/playing', $viewData);
