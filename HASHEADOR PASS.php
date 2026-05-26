<?php


function generarContrasenaAleatoria($longitud = 8) {

    $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $longitudCaracteres = strlen($caracteres);
    $contrasenaAleatoria = '';
    for ($i = 0; $i < $longitud; $i++) {
        $contrasenaAleatoria .= $caracteres[rand(0, $longitudCaracteres - 1)];
    }
    return $contrasenaAleatoria;
}


$contrasena = generarContrasenaAleatoria(8); 


$hash = password_hash($contrasena, PASSWORD_DEFAULT);


echo "Contraseña aleatoria: {$contrasena}<br>";
echo "Hash de la contraseña: {$hash}<br>";

?>



