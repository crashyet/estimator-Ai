<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 *//**
 * Auto generate AHSP code for unmapped items based on item/section category and index.
 *
 * @param string $itemName
 * @param string $secName
 * @param string $secCode
 * @param int $sIdx
 * @param int $iIdx
 * @param string $itemCode
 * @return string
 */
if (! function_exists('generate_ahsp_code')) {
    function generate_ahsp_code(
        string $itemName = '',
        string $secName = '',
        string $secCode = '',
        int $sIdx = 0,
        int $iIdx = 0,
        string $itemCode = ''
    ): string {
        $itemUpper = strtoupper(trim($itemName . ' ' . $secName));
        $itemNo = $iIdx + 1;

        // Keyword mapping to PUPR AHSP standard categories
        if (preg_match('/(PERSIAPAN|PERSIAP|PEMBERSIHAN|BOUWPLANK|PAGAR|STEKER|DIREKSI|MOBILISASI)/i', $itemUpper)) {
            return "A.2.2.1.{$itemNo}";
        }
        if (preg_match('/(TANAH|PONDASI|GALIAN|URUGAN|PEMADATAN|CUT|FILL|FONDASI|FOOTPLAT|PANCANG|BORED|STRIPPING)/i', $itemUpper)) {
            return "A.2.3.1.{$itemNo}";
        }
        if (preg_match('/(BETON|STRUKTUR|PEMBESIAN|SLOOF|KOLOM|BALOK|PLAT|BEKISTING|WIREMESH|COR|READY MIX)/i', $itemUpper)) {
            return "A.4.1.1.{$itemNo}";
        }
        if (preg_match('/(DINDING|PASANGAN|PLESTERAN|ACIAN|KERAMIK|BATA|BATAKO|HEBEL|BATA MERAH)/i', $itemUpper)) {
            return "A.4.4.1.{$itemNo}";
        }
        if (preg_match('/(KUSEN|PINTU|JENDELA|KACA|ENGSEL|KUNCI|ALUMUNIUM|KAYU)/i', $itemUpper)) {
            return "A.4.5.1.{$itemNo}";
        }
        if (preg_match('/(ATAP|RANGKA|GORDING|GENTENG|SPANDEK|PLAFON|GYPSUM|LISPLANG|TRAPESIUM)/i', $itemUpper)) {
            return "A.4.6.1.{$itemNo}";
        }
        if (preg_match('/(FINISHING|PENGECATAN|CAT|WATERPROOFING|COATING|SANITAIR|LINOLEUM)/i', $itemUpper)) {
            return "A.4.7.1.{$itemNo}";
        }
        if (preg_match('/(MEP|UTILITAS|LISTRIK|STOP|SAKLAR|KABEL|PIPA|PVC|SANITASI|SUMUR|POMPA|LAMPU|AIR|PLAMBING)/i', $itemUpper)) {
            return "A.5.1.5.{$itemNo}";
        }

        // Structural fallback based on Section Code/Index
        $secCodeTrim = trim($secCode);
        if (is_numeric($secCodeTrim) && (int)$secCodeTrim > 0) {
            $numSec = (int)$secCodeTrim;
            return "A.{$numSec}.1.1.{$itemNo}";
        }

        if (!empty($secCodeTrim) && ctype_alpha($secCodeTrim)) {
            $letterIdx = ord(strtoupper($secCodeTrim[0])) - 64;
            if ($letterIdx > 0 && $letterIdx <= 26) {
                return "A.{$letterIdx}.1.1.{$itemNo}";
            }
        }

        $secNum = $sIdx + 1;
        return "A.{$secNum}.1.1.{$itemNo}";
    }
}
