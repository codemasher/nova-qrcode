<?php
/**
 * generator.php
 *
 * @created      08.03.2022
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2022 smiley
 * @license      MIT
 */

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\QRCode;
use codemasher\NovaQRCode\NovaQRCodeOptions;
use codemasher\NovaQRCode\NovaQRCodeSVG;

const VENDOR_DIR = __DIR__.'/../vendor';

require_once VENDOR_DIR.'/autoload.php';
// the library is not on composer, so we need to include these manually
require_once __DIR__.'/../src/NovaQRCodeOptions.php';
require_once __DIR__.'/../src/NovaQRCodeSVG.php';

sleep(1);

try{
	$in = file_get_contents('php://input');

	if($in === false){
		throw new Exception('error reading input');
	}

	$data = validate_input($in);

	$options_arr = [
		'version'             => $data->version,
		'eccLevel'            => EccLevel::H,
		'outputType'          => QRCode::OUTPUT_CUSTOM,
		'outputInterface'     => NovaQRCodeSVG::class,
		'imageBase64'         => true,
		'svgAddXmlHeader'     => false,
		'markupDark'          => '',
		'markupLight'         => '',
		'addQuietzone'        => $data->quietzone,
		'drawCircularModules' => $data->circularmodules,
		'circleRadius'        => $data->circleradius,
		'keepAsSquare'        => $data->keepassquare,
		'svgConnectPaths'     => $data->connectpaths,
		'svgLogo'             => $data->logo,
		'clearLogoSpace'      => $data->clearlogospace,
		'svgLogoScale'        => $data->logoscale / 100,
		'svgLogoCssClass'     => 'logo',
		// https://developer.mozilla.org/en-US/docs/Web/SVG/Element/linearGradient
		'svgDefs'             => '
	<style><![CDATA[
		.dark{fill: #'.$data->qrcode_dark.';}
		.light{fill: #'.$data->qrcode_light.';}
		.logo{fill: #'.$data->qrcode_logo.';}
	]]></style>',
	];

	$qrcode = new QRCode(new NovaQRCodeOptions($options_arr));
	$qrcode->addByteSegment($data->inputstring);

	send_response(['qrcode' => $qrcode->render()]);
}
// Pokémon exception handler
catch(Throwable $e){
	header('HTTP/1.1 500 Internal Server Error');
	send_response(['error' => $e->getMessage()]);
}

exit;

/**
 * @param array $response
 */
function send_response(array $response){
	header('Content-type: application/json;charset=utf-8;');
	echo json_encode($response);
	exit;
}

/**
 * @throws \Exception
 */
function validate_input(string $in):stdClass{
	$in  = json_decode($in);
	$out = new stdClass;

	if(!$in){
		throw new Exception('error decoding json');
	}

	if(!isset($in->inputstring)){
		throw new Exception('invalid input');
	}

	// data
	$str = trim($in->inputstring); // todo: sanitize/validatae if necessary

	if(empty($str)){
		throw new Exception('empty input string');
	}

	$out->inputstring = $str;

	// logo
	$out->logo = null;

	if(isset($in->logo) && !empty($in->logo)){
		$file = strtolower(trim($in->logo));

		if(preg_match('/^[a-z]+$/', $file)){
			// @todo
			$out->logo = sprintf(VENDOR_DIR.'/simple-icons/simple-icons/icons/%s.svg', $file);
		}
	}

	// keep patterns as square
	$out->keepassquare = [];

	if(isset($in->squarefinder)){
		$out->keepassquare[] = QRMatrix::M_FINDER|QRMatrix::IS_DARK;
		$out->keepassquare[] = QRMatrix::M_FINDER_DOT;
	}

	if(isset($in->squarealignment)){
		$out->keepassquare[] = QRMatrix::M_ALIGNMENT|QRMatrix::IS_DARK;
	}

	// booleans
	foreach(['circularmodules', 'clearlogospace', 'connectpaths', 'quietzone'] as $v){
		$out->{$v} = isset($in->{$v});
	}

	// integers
	foreach(['logoscale', 'version'] as $v){
		$out->{$v} = intval($in->{$v} ?? 0);
	}

	// floats
	foreach(['circleradius'] as $v){
		$out->{$v} = floatval($in->{$v} ?? 0);
	}

	// colors
	$colors = [
		'qrcode_light' => 'ffffff',
		'qrcode_dark'  => '000000',
		'qrcode_logo'  => '000000',
	];

	foreach($colors as $v => $default){
		$out->{$v} = isset($in->{$v}) && preg_match('/[a-f\d]{6}/i', $in->{$v}) === 1 ? $in->{$v} : $default;
	}

	return $out;
}
