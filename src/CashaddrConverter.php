<?php

namespace Kiwina\CashaddrConverter;

use Exception;

class CashaddrConverter
{
    public const ALPHABET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
    public const CHARSET = 'qpzry9x8gf2tvdw0s3jn54khce6mua7l';
    public const ALPHABET_MAP =
        [-1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
            -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
            -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
            -1, 0, 1, 2, 3, 4, 5, 6, 7, 8, -1, -1, -1, -1, -1, -1,
            -1, 9, 10, 11, 12, 13, 14, 15, 16, -1, 17, 18, 19, 20, 21, -1,
            22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, -1, -1, -1, -1, -1,
            -1, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, -1, 44, 45, 46,
            47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, -1, -1, -1, -1, -1];
    public const BECH_ALPHABET =
        [-1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
            -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
            -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
            15, -1, 10, 17, 21, 20, 26, 30, 7, 5, -1, -1, -1, -1, -1, -1,
            -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
            -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
            -1, 29, -1, 24, 13, 25, 9, 8, 23, -1, 18, 22, 31, 27, 19, -1,
            1, 0, 3, 16, 11, 28, 12, 14, 6, 4, 2, -1, -1, -1, -1, -1];
    public const EXPAND_PREFIX_UNPROCESSED = [2, 9, 20, 3, 15, 9, 14, 3, 1, 19, 8, 0];
    public const EXPAND_PREFIX_TESTNET_UNPROCESSED = [2, 3, 8, 20, 5, 19, 20, 0];
    public const EXPAND_PREFIX = 1058337025301;
    public const EXPAND_PREFIX_TESTNET = 584719417569;
    public const BASE16 = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, -1, -1, -1, -1, -1, -1, -1, -1,
        -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, 10, 11, 12,
        13, 14, 15];

    /**
     * @throws CashaddrConverterException
     */
    public function __construct()
    {
        if (PHP_INT_SIZE < 5) {

            // Requires x64 system and PHP!
            throw new CashaddrConverterException('Run it on a x64 system (+ 64 bit PHP)');
        }
    }

    /**
     * @throws CashaddrConverterException
     */
    public function convertToCashaddr($address): string
    {
        return self::old2new($address);
    }

    /**
     * @throws CashaddrConverterException
     */
    public function convertFromCashaddr($address): string
    {
        return self::new2old($address, true);
    }


    /**
     * convertBits is the internal function to convert 256-based bytes
     * to base-32 grouped bit arrays and vice versa.
     * @param array $data Data whose bits to be re-grouped
     * @param integer $fromBits Bits per input group of the $data
     * @param integer $toBits Bits to be put to each output group
     * @param boolean $pad Whether to add extra zeroes
     * @return array $ret
     * @throws CashaddrConverterException
     */
    private static function convertBits(array $data, int $fromBits, int $toBits, bool $pad = true): array
    {
        $acc = 0;
        $bits = 0;
        $ret = [];
        $maxv = (1 << $toBits) - 1;
        $maxacc = (1 << ($fromBits + $toBits - 1)) - 1;

        foreach ($data as $iValue) {
            $value = $iValue;

            if ($value < 0 || $value >> $fromBits !== 0) {
                throw new CashaddrConverterException('Error!');
            }

            $acc = (($acc << $fromBits) | $value) & $maxacc;
            $bits += $fromBits;

            while ($bits >= $toBits) {
                $bits -= $toBits;
                $ret[] = (($acc >> $bits) & $maxv);
            }
        }

        if ($pad) {
            if ($bits) {
                $ret[] = ($acc << $toBits - $bits) & $maxv;
            }
        } else if ($bits >= $fromBits || ((($acc << ($toBits - $bits))) & $maxv)) {
            throw new CashaddrConverterException('Error!');
        }

        return $ret;
    }

    /**
     * polyMod is the internal function create BCH codes.
     * @param array $var 5-bit grouped data array whose polyMod to be calculated.
     * @param integer c Starting value, 1 if the prefix is appended to the array.
     * @return integer $polymodValue polymod result
     */
    private static function polyMod(array $var, $c = 1): int
    {
        foreach ($var as $iValue) {
            $c0 = $c >> 35;
            $c = (($c & 0x07ffffffff) << 5) ^
                ($iValue) ^
                (-($c0 & 1) & 0x98f2bc8e61) ^
                (-($c0 & 2) & 0x79b76d99e2) ^
                (-($c0 & 4) & 0xf33e5fb3c4) ^
                (-($c0 & 8) & 0xae2eabe2a8) ^
                (-($c0 & 16) & 0x1e4f43e470);
        }

        return $c ^ 1;
    }

    /**
     * rebuildAddress is the internal function to recreate error
     * corrected addresses.
     * @param array $addressBytes
     * @return string $correctedAddress
     */
    private static function rebuildAddress(array $addressBytes): string
    {
        $ret = '';
        $i = 0;

        while ($addressBytes[$i] !== 0) {
            // 96 = ord('a') & 0xe0
            $ret .= chr(96 + $addressBytes[$i]);
            $i++;
        }

        $ret .= ':';
        $len = count($addressBytes);
        for ($i++; $i < $len; $i++) {
            $ret .= self::CHARSET[$addressBytes[$i]];
        }

        return $ret;
    }

    /**
     * old2new converts an address in old format to the new Cash Address format.
     * @param string $oldAddress (either Mainnet or Testnet)
     * @return string $newAddress Cash Address result
     * @throws CashaddrConverterException
     */
    public static function old2new(string $oldAddress): string
    {
        $bytes = [0];

        for ($x = 0, $xMax = strlen($oldAddress); $x < $xMax; $x++) {
            $carry = ord($oldAddress[$x]);
            if ($carry > 127 || ((($carry = self::ALPHABET_MAP[$carry]) === -1))) {
                throw new CashaddrConverterException('Unexpected character in address!');
            }

            foreach ($bytes as $j => $jValue) {
                $carry += $jValue * 58;
                $bytes[$j] = $carry & 0xff;
                $carry >>= 8;
            }

            while ($carry !== 0) {
                $bytes[] = $carry & 0xff;
                $carry >>= 8;
            }
        }

        for ($numZeros = 0; $numZeros < strlen($oldAddress) && $oldAddress[$numZeros] === '1'; $numZeros++) {
            $bytes[] = 0;
        }

        // reverse array
        $answer = [];

        for ($i = count($bytes) - 1; $i >= 0; $i--) {
            $answer[] = $bytes[$i];
        }

        $version = $answer[0];
        $payload = array_slice($answer, 1, count($answer) - 5);

        if (count($payload) % 4 !== 0) {
            throw new CashaddrConverterException('Unexpected address length!');
        }

        // Assume the checksum of the old address is right
        // Here, the Cash Address conversion starts
        if ($version === 0x00) {
            // P2PKH
            $addressType = 0;
            $realNet = true;
        } else if ($version === 0x05) {
            // P2SH
            $addressType = 1;
            $realNet = true;
        } else if ($version === 0x6f) {
            // Testnet P2PKH
            $addressType = 0;
            $realNet = false;
        } else if ($version === 0xc4) {
            // Testnet P2SH
            $addressType = 1;
            $realNet = false;
        } else if ($version === 0x1c) {
            // BitPay P2PKH
            $addressType = 0;
            $realNet = true;
        } else if ($version === 0x28) {
            // BitPay P2SH
            $addressType = 1;
            $realNet = true;
        } else {
            throw new CashaddrConverterException('Unknown address type!');
        }

        $encodedSize = (count($payload) - 20) / 4;

        $versionByte = ($addressType << 3) | $encodedSize;
        $data = array_merge([$versionByte], $payload);
        $payloadConverted = self::convertBits($data, 8, 5);
        $arr = array_merge($payloadConverted, [0, 0, 0, 0, 0, 0, 0, 0]);
        if ($realNet) {
            $expand_prefix = self::EXPAND_PREFIX;
            $ret = 'bitcoincash:';
        } else {
            $expand_prefix = self::EXPAND_PREFIX_TESTNET;
            $ret = 'bchtest:';
        }
        $mod = self::polymod($arr, $expand_prefix);
        $checksum = [0, 0, 0, 0, 0, 0, 0, 0];

        for ($i = 0; $i < 8; $i++) {
            // Convert the 5-bit groups in mod to checksum values.
            // $checksum[$i] = ($mod >> 5*(7-$i)) & 0x1f;
            $checksum[$i] = ($mod >> (5 * (7 - $i))) & 0x1f;
        }

        $combined = array_merge($payloadConverted, $checksum);
        foreach ($combined as $iValue) {
            $ret .= self::CHARSET[$iValue];
        }

        return $ret;
    }

    /**
     * Decodes Cash Address.
     * @param string $inputNew New address to be decoded.
     * @param boolean $shouldFixErrors Whether to fix typing errors.
     * @return array|string $decoded Returns decoded byte array if it can be decoded.
     * @throws CashaddrConverterException
     */
    public static function decodeNewAddr(string $inputNew, bool $shouldFixErrors): array|string
    {
        $inputNew = strtolower($inputNew);
        if (!str_contains($inputNew, ':')) {
            $afterPrefix = 0;
            $expand_prefix = self::EXPAND_PREFIX;
            $isTestnetAddressResult = false;
        } else if (str_starts_with($inputNew, 'bitcoincash:')) {
            $afterPrefix = 12;
            $expand_prefix = self::EXPAND_PREFIX;
            $isTestnetAddressResult = false;
        } else if (str_starts_with($inputNew, 'bchtest:')) {
            $afterPrefix = 8;
            $expand_prefix = self::EXPAND_PREFIX_TESTNET;
            $isTestnetAddressResult = true;
        } else {
            throw new CashaddrConverterException('Unknown address type');
        }

        $data = [];
        $len = strlen($inputNew);
        for (; $afterPrefix < $len; $afterPrefix++) {
            $i = ord($inputNew[$afterPrefix]);
            if ($i > 127 || (($i = self::BECH_ALPHABET[$i]) === -1)) {
                throw new CashaddrConverterException('Unexpected character in address!');
            }
            $data[] = $i;
        }

        $checksum = self::polyMod($data, $expand_prefix);

        if ($checksum !== 0) {
            if ($expand_prefix === self::EXPAND_PREFIX_TESTNET) {
                $unexpand_prefix = self::EXPAND_PREFIX_TESTNET_UNPROCESSED;
            } else {
                $unexpand_prefix = self::EXPAND_PREFIX_UNPROCESSED;
            }
            // Checksum is wrong!
            // Try to fix up to two errors
            if ($shouldFixErrors) {
                $syndromes = array();
                foreach ($data as $p => $pValue) {
                    for ($e = 1; $e < 32; $e++) {
                        $data[$p] ^= $e;
                        $c = self::polyMod($data, $expand_prefix);
                        if ($c === 0) {
                            return self::rebuildAddress(array_merge($unexpand_prefix, $data));
                        }
                        $syndromes[$c ^ $checksum] = $p * 32 + $e;
                        $data[$p] ^= $e;
                    }
                }

                foreach ($syndromes as $s0 => $pe) {
                    if (array_key_exists($s0 ^ $checksum, $syndromes)) {
                        $data[$pe >> 5] ^= $pe % 32;
                        $data[$syndromes[$s0 ^ $checksum] >> 5] ^= $syndromes[$s0 ^ $checksum] % 32;
                        return self::rebuildAddress(array_merge($unexpand_prefix, $data));
                    }
                }
                throw new CashaddrConverterException('Can\'t correct typing errors!');
            }
        }
        return [$data, $isTestnetAddressResult];
    }

    /**
     * Corrects Cash Address typing errors.
     * @param string $inputNew Cash Address to be corrected.
     * @return array|string $correctedAddress Error corrected address, or the input itself
     * if there are no errors.
     * @throws CashaddrConverterException
     */
    public static function fixCashAddrErrors(string $inputNew): array|string
    {
        try {
            $corrected = self::decodeNewAddr($inputNew, true);
            if (is_array($corrected)) {
                return $inputNew;
            }

            return $corrected;
        } catch (CashaddrConverterException $e) {
            throw $e;
        }
    }


    /**
     * new2old converts an address in the Cash Address format to the old format.
     * @param string $inputNew Cash Address (either mainnet or testnet)
     * @param boolean $shouldFixErrors Whether to fix typing errors.
     * @return string $oldAddress Old style 1... or 3... address
     * @throws CashaddrConverterException
     */
    public static function new2old(string $inputNew, bool $shouldFixErrors): string
    {
        try {
            [$corrected, $isTestnet] = self::decodeNewAddr($inputNew, $shouldFixErrors);
            if (is_array($corrected)) {
                $values = $corrected;
            } else {
                [$values, $isTestnet] = self::decodeNewAddr($corrected, false);
            }
        } catch (Exception) {
            throw new CashaddrConverterException('Error');
        }

        $values = self::convertBits(array_slice($values, 0, count($values) - 8), 5, 8, false);
        $addressType = $values[0] >> 3;
        $addressHash = array_slice($values, 1, 21);

        // Encode Address
        if ($isTestnet) {
            if ($addressType) {
                $bytes = [0xc4];
            } else {
                $bytes = [0x6f];
            }
        } else if ($addressType) {
            $bytes = [0x05];
        } else {
            $bytes = [0x00];
        }
        $bytes = array_merge($bytes, $addressHash);
        $merged = array_merge($bytes, self::doubleSha256ByteArray($bytes));
        $digits = [0];
        $merged_len = count($merged);
        foreach ($merged as $iValue) {
            $carry = $iValue;
            foreach ($digits as $j => $jValue) {
                $carry += $jValue << 8;
                $digits[$j] = $carry % 58;
                $carry = intdiv($carry, 58);
            }

            while ($carry !== 0) {
                $digits[] = $carry % 58;
                $carry = intdiv($carry, 58);
            }
        }

        // leading zero bytes
        for ($i = 0; $i < $merged_len && $merged[$i] === 0; $i++) {
            $digits[] = 0;
        }

        // reverse
        $converted = '';
        for ($i = count($digits) - 1; $i >= 0; $i--) {
            if ($digits[$i] > strlen(self::ALPHABET)) {
                throw new CashaddrConverterException('Error!');
            }
            $converted .= self::ALPHABET[$digits[$i]];
        }

        return $converted;
    }

    /**
     * internal function to calculate sha256
     * @param array $byteArray Byte array of data to be hashed
     * @return array $hashResult First four bytes of sha256 result
     */
    private static function doubleSha256ByteArray(array $byteArray): array
    {
        $stringToBeHashed = '';
        foreach ($byteArray as $iValue) {
            $stringToBeHashed .= chr($iValue);
        }
        $hash = hash('sha256', $stringToBeHashed);
        $hashArray = [];
        for ($i = 0; $i < 32; $i++) {
            $hashArray[] = self::BASE16[ord($hash[2 * $i]) - 48] * 16 + self::BASE16[ord($hash[2 * $i + 1]) - 48];
        }
        $stringToBeHashed = '';
        for ($i = 0; $i < 32; $i++) {
            $stringToBeHashed .= chr($hashArray[$i]);
        }

        $hashArray = [];
        $hash = hash('sha256', $stringToBeHashed);
        for ($i = 0; $i < 4; $i++) {
            $hashArray[] = self::BASE16[ord($hash[2 * $i]) - 48] * 16 + self::BASE16[ord($hash[2 * $i + 1]) - 48];
        }
        return $hashArray;
    }
}
