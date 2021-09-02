<?php
class Encrypt
{
    public $LoremIpsum="Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";

    public function __construct()
    {
    }

    public function encrypt($plaintext)
    {
        # --- ENCRYPTION ---
        # la clave debería ser binaria aleatoria, use scrypt, bcrypt o PBKDF2 para
        # convertir un string en una clave
        # la clave se especifica en formato hexadecimal
        $key = pack('H*', "bcb04b7e103a0cd8b54763051cef08bc55abe029fdebae5e1d417e2ffb2a00a3");

        # mostrar el tamaño de la clave, use claves de 16, 24 o 32 bytes para AES-128, 192
        # y 256 respectivamente
        $key_size =  strlen($key);
        //echo "Tamaño de la clave: " . $key_size . "\n";

        //$plaintext = "Este estring estaba encriptado con AES-256 / CBC / ZeroBytePadding.";

        # crear una aleatoria IV para utilizarla co condificación CBC
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

        # crea un texto cifrado compatible con AES (tamaño de bloque Rijndael = 128)
        # para hacer el texto confidencial
        # solamente disponible para entradas codificadas que nunca finalizan con el
        # el valor  00h (debido al relleno con ceros)
        $ciphertext = mcrypt_encrypt(
            MCRYPT_RIJNDAEL_128,
            $key,
                                     $plaintext,
            MCRYPT_MODE_CBC,
            $iv
        );

        # anteponer la IV para que esté disponible para el descifrado
        $ciphertext = $iv . $ciphertext;

        # codificar el texto cifrado resultante para que pueda ser representado por un string
        $ciphertext_base64 = base64_encode($ciphertext);

        return $ciphertext_base64;
    }

    public function desEncrypt($ciphertext_base64)
    {
        # la clave debería ser binaria aleatoria, use scrypt, bcrypt o PBKDF2 para
        # convertir un string en una clave
        # la clave se especifica en formato hexadecimal
        $key = pack('H*', "bcb04b7e103a0cd8b54763051cef08bc55abe029fdebae5e1d417e2ffb2a00a3");

        # crear una aleatoria IV para utilizarla co condificación CBC
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

        # --- DESCIFRADO ---
        $ciphertext_dec = base64_decode($ciphertext_base64);

        # recupera la IV, iv_size debería crearse usando mcrypt_get_iv_size()
        $iv_dec = substr($ciphertext_dec, 0, $iv_size);

        # recupera el texto cifrado (todo excepto el $iv_size en el frente)
        $ciphertext_dec = substr($ciphertext_dec, $iv_size);

        # podrían eliminarse los caracteres con valor 00h del final del texto puro
        $plaintext_dec = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec);

        //$plaintext_dec=str_replace("\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000\u0000","",$plaintext_dec);
        return  $plaintext_dec;
    }
}
