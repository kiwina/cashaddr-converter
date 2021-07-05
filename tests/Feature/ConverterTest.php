<?php


namespace Kiwina\CashaddrConverter\Tests\Feature;

use Kiwina\CashaddrConverter\CashaddrConverter;

$p2pkh = '12higDjoCCNXSA95xZMWUdPvXNmkAduhWv';
$p2sh = '342ftSRCvFHfCeFFBuz4xwbeqnDw6BGUey';
$malformed = 'bitcoincash:qpm2qsznhks23z7629mas6s4cwzf74vcwvy22gdx6a';
$bitpayp2pkh = 'CWUmjdL9q4G1Rz6o2MDGoMExivHgEnCgDx';
$bitpayp2sh = 'HHrv7h4TkshW2TGLdJ1NBg5LsQzPQwLFGE';
$testnetp2pkh = 'mipcBbFg9gMiCh81Kj8tqqdgoZub1ZJRfn';
$testnetp2sh = '2MzQwSSnBHWHqSAqtTVQ6v47XtaisrJa1Vc';

it('Test P2PKH addresses', function () {
    $address = '12higDjoCCNXSA95xZMWUdPvXNmkAduhWv';
    $old2new = CashaddrConverter::old2new($address);
    $new2old = CashaddrConverter::new2old($old2new, false);
    $this->assertEquals($address, $new2old);
});

it('Test P2SH addresses', function () {
    $address = '342ftSRCvFHfCeFFBuz4xwbeqnDw6BGUey';
    $old2new = CashaddrConverter::old2new($address);
    $new2old = CashaddrConverter::new2old($old2new, false);
    $this->assertEquals($address, $new2old);
});

it('Test error correction', function () {
    $address = 'bitcoincash:qpm2qsznhks23z7629mas6s4cwzf74vcwvy22gdx6a';
    [$corrected, $isTestnet] = CashaddrConverter::decodeNewAddr($address, true);
   
    //$r = CashaddrConverter::new2old($address, true);
   //$r = CashaddrConverter::fixCashAddrErrors($r);

    //$this->assertEquals("bitcoincash:qpm2qsznhks23z7629mms6s4cwef74vcwvy22gdx6a", $r);
});

it('Test BitPay P2PKH addresses', function () {
    $address = 'CWUmjdL9q4G1Rz6o2MDGoMExivHgEnCgDx';
    $r = CashaddrConverter::old2new($address);
    $this->assertEquals('bitcoincash:qzvmc7962aaftgglrg6y6nf2u40jlptmnqhpeu5t83', $r);
});

it('Test BitPay P2SH addresses', function () {
    $address = 'HHrv7h4TkshW2TGLdJ1NBg5LsQzPQwLFGE';
    $r = CashaddrConverter::old2new($address);
    $this->assertEquals('bitcoincash:pp7xwa0zpclf8rfd06whntp3qyyt55qamvfsugp2zx', $r);
});

it('Test Testnet P2PKH addresses', function () {
    $address = 'mipcBbFg9gMiCh81Kj8tqqdgoZub1ZJRfn';
    $r = CashaddrConverter::old2new($address);
    $r = CashaddrConverter::new2old($r, true);
    $this->assertEquals($address, $r);
});

it('Test Testnet P2SH addresses', function () {
    $address = '2MzQwSSnBHWHqSAqtTVQ6v47XtaisrJa1Vc';
    $r = CashaddrConverter::old2new($address);
    $r = CashaddrConverter::new2old($r, true);
    $this->assertEquals($address, $r);
});
