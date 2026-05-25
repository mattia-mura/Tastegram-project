<?php
// config/helpers.php
// Includi questo file in tutte le pagine dopo auth.php

/**
 * Restituisce il path HTML corretto dell'avatar.
 * 
 * - 'default_avatar.png'  → ../img/default_avatar.png
 * - 'avatar_3_xxx.jpg'    → ../img/uploads/avatars/avatar_3_xxx.jpg
 * 
 * @param string $avatarUrl  Valore della colonna avatar_url nel DB
 * @param string $base       Path base relativo alla pagina chiamante (default: '../img/')
 */
function avatarSrc(string $avatarUrl, string $base = '../img/'): string {
    if (empty($avatarUrl) || $avatarUrl === 'default_avatar.png') {
        return $base . 'default_avatar.png';
    }
    // Avatar personalizzato — sta in uploads/avatars/
    return $base . 'uploads/avatars/' . $avatarUrl;
}

/**
 * Restituisce il path HTML dell'immagine di un post.
 * 
 * @param string|null $imagePath  Valore della colonna image_path nel DB
 * @param string $base            Path base relativo alla pagina chiamante
 */
function postImageSrc(?string $imagePath, string $base = '../img/'): string {
    if (empty($imagePath)) {
        return ''; // nessuna immagine
    }
    return $base . 'uploads/foto/' . $imagePath;
}
