<?php
// Comprobante en PDF de la ficha (hoja 1: ficha con datos y documentos; hoja 2: métodos de pago e indicaciones).
// PDF mínimo escrito a mano (tipografía Helvetica estándar): no necesita librerías ni guarda archivos en el servidor.

// Anchos Helvetica (milésimas de em) de los caracteres ASCII 32..126, para ajustar líneas largas.
const FICHA_ANCHOS = array( 278, 278, 355, 556, 556, 889, 667, 191, 333, 333, 389, 584, 278, 333, 278, 278, 556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 278, 278, 584, 584, 584, 556, 1015, 667, 667, 722, 722, 667, 611, 778, 722, 278, 500, 667, 556, 833, 722, 778, 667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 278, 278, 278, 469, 556, 333, 556, 556, 500, 556, 556, 278, 556, 556, 222, 222, 500, 222, 833, 556, 556, 556, 556, 333, 500, 278, 556, 500, 722, 500, 500, 500, 334, 260, 334, 584 );

class Ficha_PDF {
	public $pags = array();
	public $img = null; // logo PNG (RGB o RGBA, 8 bits, sin entrelazado); si no cumple, el PDF sale sin logo
	private $c = '';
	const H = 842;
	function nueva() {
		$this->c && $this->pags[] = $this->c;
		$this->c = '';
	}
	private function cp( $s ) {
		return str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), mb_convert_encoding( $s, 'Windows-1252', 'UTF-8' ) );
	}
	function ancho( $s, $t, $neg = false ) {
		$s = strtr( remove_accents( $s ), "\t", ' ' );
		$w = 0;
		foreach ( str_split( $s ) as $ch ) {
			$o  = ord( $ch );
			$w += FICHA_ANCHOS[ $o >= 32 && $o <= 126 ? $o - 32 : 0 ];
		}
		return $w * $t / 1000 * ( $neg ? 1.08 : 1 );
	}
	// Color en #rrggbb para texto (t) o trazo/relleno (g).
	private function col( $hex ) {
		return implode( ' ', array_map( fn( $i ) => round( hexdec( substr( $hex, $i, 2 ) ) / 255, 3 ), array( 1, 3, 5 ) ) );
	}
	function texto( $x, $y, $s, $t = 10, $neg = false, $color = '#1e2a44', $der = false ) {
		if ( $der ) {
			$x -= $this->ancho( $s, $t, $neg );
		}
		$this->c .= sprintf( "BT /%s %s Tf %s rg %.1f %.1f Td (%s) Tj ET\n", $neg ? 'F2' : 'F1', $t, $this->col( $color ), $x, self::H - $y, $this->cp( $s ) );
	}
	function linea( $x1, $y1, $x2, $y2, $color = '#1e2a44', $g = .8 ) {
		$this->c .= sprintf( "%s RG %s w %.1f %.1f m %.1f %.1f l S\n", $this->col( $color ), $g, $x1, self::H - $y1, $x2, self::H - $y2 );
	}
	function caja( $x, $y, $w, $h, $borde = '#1e2a44', $relleno = '' ) {
		$this->c .= ( $relleno ? $this->col( $relleno ) . " rg\n" : '' ) . $this->col( $borde ) . sprintf( " RG .8 w %.1f %.1f %.1f %.1f re %s\n", $x, self::H - $y - $h, $w, $h, $relleno ? 'B' : 'S' );
	}
	// Párrafo ajustado al ancho; devuelve la y siguiente.
	function parrafo( $x, $y, $w, $s, $t = 10, $neg = false, $color = '#1e2a44', $sep = 1.35 ) {
		foreach ( preg_split( '/\R/', $s ) as $parr ) {
			$l = '';
			foreach ( preg_split( '/\s+/', trim( $parr ) ) as $p ) {
				if ( $l && $this->ancho( "$l $p", $t, $neg ) > $w ) {
					$this->texto( $x, $y, $l, $t, $neg, $color );
					$y += $t * $sep;
					$l  = $p;
				} else {
					$l = trim( "$l $p" );
				}
			}
			$this->texto( $x, $y, $l, $t, $neg, $color );
			$y += $t * $sep;
		}
		return $y;
	}
	// Lee un PNG para incrustarlo sin librerías (misma técnica que FPDF: el canal alfa va como máscara aparte).
	function logo( $ruta ) {
		$d = is_readable( $ruta ) ? file_get_contents( $ruta ) : '';
		if ( "\x89PNG\r\n\x1a\n" !== substr( $d, 0, 8 ) || ! function_exists( 'gzuncompress' ) ) {
			return;
		}
		$pos = 8;
		$idat = '';
		while ( $pos < strlen( $d ) ) {
			$n = unpack( 'N', substr( $d, $pos, 4 ) )[1];
			$t = substr( $d, $pos + 4, 4 );
			if ( 'IHDR' === $t ) {
				$h = unpack( 'Nw/Nh/Cbit/Cct/Ccomp/Cfil/Cint', substr( $d, $pos + 8, 13 ) );
			} elseif ( 'IDAT' === $t ) {
				$idat .= substr( $d, $pos + 8, $n );
			}
			$pos += 12 + $n;
		}
		if ( empty( $h ) || 8 !== $h['bit'] || $h['int'] || ! in_array( $h['ct'], array( 2, 6 ), true ) ) {
			return;
		}
		$alpha = null;
		if ( 6 === $h['ct'] ) {
			$raw = gzuncompress( $idat );
			$len = 4 * $h['w'];
			$col = $alp = '';
			for ( $i = 0; $i < $h['h']; $i++ ) {
				$o    = ( 1 + $len ) * $i;
				$lin  = substr( $raw, $o + 1, $len );
				$col .= $raw[ $o ] . preg_replace( '/(.{3})./s', '$1', $lin );
				$alp .= $raw[ $o ] . preg_replace( '/.{3}(.)/s', '$1', $lin );
			}
			$idat  = gzcompress( $col );
			$alpha = gzcompress( $alp );
		}
		$this->img = array( 'w' => $h['w'], 'h' => $h['h'], 'datos' => $idat, 'alfa' => $alpha );
	}
	function imagen( $x, $y, $w, $h ) {
		if ( $this->img ) {
			$this->c .= sprintf( "q %.1f 0 0 %.1f %.1f %.1f cm /Im1 Do Q\n", $w, $h, $x, self::H - $y - $h );
		}
	}
	function salida() {
		$this->nueva();
		$o = array( '<< /Type /Catalog /Pages 2 0 R >>', '', '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>', '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>' );
		$res = '/Font << /F1 3 0 R /F2 4 0 R >>';
		if ( $this->img ) {
			$i    = $this->img;
			$prm  = '/Filter /FlateDecode /DecodeParms << /Predictor 15 /Colors 3 /BitsPerComponent 8 /Columns ' . $i['w'] . ' >>';
			if ( $i['alfa'] ) {
				$o[] = '<< /Type /XObject /Subtype /Image /Width ' . $i['w'] . ' /Height ' . $i['h'] . ' /ColorSpace /DeviceGray /BitsPerComponent 8 /Filter /FlateDecode /DecodeParms << /Predictor 15 /Colors 1 /BitsPerComponent 8 /Columns ' . $i['w'] . ' >> /Length ' . strlen( $i['alfa'] ) . " >>\nstream\n" . $i['alfa'] . "\nendstream";
			}
			$o[]  = '<< /Type /XObject /Subtype /Image /Width ' . $i['w'] . ' /Height ' . $i['h'] . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 ' . $prm . ( $i['alfa'] ? ' /SMask ' . count( $o ) . ' 0 R' : '' ) . ' /Length ' . strlen( $i['datos'] ) . " >>\nstream\n" . $i['datos'] . "\nendstream";
			$res .= ' /XObject << /Im1 ' . count( $o ) . ' 0 R >>';
		}
		$kids = array();
		foreach ( $this->pags as $i => $c ) {
			$n      = count( $o ) + 1;
			$kids[] = "$n 0 R";
			$o[]    = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << $res >> /Contents " . ( $n + 1 ) . ' 0 R >>';
			$o[]    = '<< /Length ' . strlen( $c ) . " >>\nstream\n$c\nendstream";
		}
		$o[1] = '<< /Type /Pages /Kids [' . implode( ' ', $kids ) . '] /Count ' . count( $kids ) . ' >>';
		$pdf  = "%PDF-1.4\n";
		$pos  = array();
		foreach ( $o as $i => $d ) {
			$pos[] = strlen( $pdf );
			$pdf  .= ( $i + 1 ) . " 0 obj\n$d\nendobj\n";
		}
		$x = strlen( $pdf );
		$pdf .= "xref\n0 " . ( count( $o ) + 1 ) . "\n0000000000 65535 f \n";
		foreach ( $pos as $p ) {
			$pdf .= sprintf( "%010d 00000 n \n", $p );
		}
		return $pdf . 'trailer << /Size ' . ( count( $o ) + 1 ) . " /Root 1 0 R >>\nstartxref\n$x\n%%EOF";
	}
}
