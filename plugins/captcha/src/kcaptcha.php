<?php
/**
 * KCAPTCHA PROJECT
 * Automatic test to tell computers and humans apart.
 *
 * System requirements: PHP 4.0.6+ w/ GD
 *
 * @version           2.1
 * @package           KCAPTCHA
 * @author            Kruglov Sergei <kruglov@yandex.ru>
 * @copyright     (C) 2006-2016 Kruglov Sergei www.captcha.ru, www.kruglov.ru
 * @copyright     (C) 2021 Vladimir Globulopolis xn--80aeqbhthr9b.com
 * @license           KCAPTCHA is a free software. You can freely use it for building own site or software.
 *                    If you use this software as a part of own sofware, you must leave copyright notices intact or add
 *                    KCAPTCHA copyright notices to own.
 */

namespace Joomla\Plugin\Captcha\Kcaptcha;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Path;

/**
 * @package  Kcaptcha
 *
 * @since    2.0
 */
class Kcaptcha
{
	/**
	 * @var    string  Do not change without changing font files!
	 * @since  2.0
	 */
	protected $alphabet = "0123456789abcdefghijklmnopqrstuvwxyz";

	/**
	 * @var    string  Symbols used to draw CAPTCHA.
	 *                 Alphabet without similar symbols (o=0, 1=l, i=j, t=f)
	 * @since  2.0
	 */
	protected $allowedSymbols = "23456789abcdegikpqsvxyz";

	/**
	 * @var    string  Folder with fonts
	 * @since  2.0
	 */
	protected $fontsDir = 'fonts';

	/**
	 * @var    integer  CAPTCHA string length
	 * @since  2.0
	 */
	protected $length = 5;

	/**
	 * @var    integer  CAPTCHA image width
	 * @since  2.0
	 */
	protected $width = 160;

	/**
	 * @var    integer  CAPTCHA image height
	 * @since  2.0
	 */
	protected $height = 80;

	/**
	 * @var    integer  Symbol's vertical fluctuation amplitude
	 * @since  2.0
	 */
	protected $fluctuationAmplitude = 8;

	/**
	 * @var    integer  Noise.
	 *                  0 - no white noise
	 * @since  2.0
	 */
	protected $whiteNoiseDensity = 1 / 6;

	/**
	 * @var    integer  Noise.
	 *                  0 - no black noise
	 * @since  2.0
	 */
	protected $blackNoiseDensity = 1 / 30;

	/**
	 * @var    boolean  Increase safety by prevention of spaces between symbols
	 * @since  2.0
	 */
	protected $noSpaces = true;

	/**
	 * @var    boolean
	 * @since  2.0
	 */
	protected $showCredits = false;

	/**
	 * @var    string
	 * @since  2.0
	 */
	protected $credits = '';

	/**
	 * @var    array|string  CAPTCHA text color. Can be RGB or HEX
	 * @since  2.0
	 */
	protected $foregroundColor = array(180, 180, 180);

	/**
	 * @var    array|string  CAPTCHA background color. Can be RGB or HEX
	 * @since  2.0
	 */
	protected $backgroundColor = array(246, 246, 246);

	/**
	 * @var    integer  JPEG quality of CAPTCHA image (bigger is better quality, but larger file size)
	 * @since  2.0
	 */
	protected $jpegQuality = 90;

	/**
	 * @var    boolean  Randomize background colors
	 * @since  2.0
	 */
	protected $rand = false;

	/**
	 * @var    boolean  Add noises to image.
	 * @since  2.0
	 */
	protected $noise = true;

	/**
	 * @var    string  Secret string
	 * @since  2.0
	 */
	protected $keyString = null;

	/**
	 * Class constructor
	 *
	 * @param   array  $options  Array of options
	 *
	 * @since   2.0
	 */
	public function __construct(array $options = array())
	{
		if (is_array($options))
		{
			foreach ($options as $property => $value)
			{
				$this->set($property, $value);
			}
		}

		$this->length = mt_rand(4, $this->length);

		if ($this->rand)
		{
			$this->foregroundColor = array(
				mt_rand(0, $this->foregroundColor[0]),
				mt_rand(0, $this->foregroundColor[0]),
				mt_rand(0, $this->foregroundColor[0])
			);
			$this->backgroundColor = array(
				mt_rand(0, $this->backgroundColor[0]),
				mt_rand(0, $this->backgroundColor[0]),
				mt_rand(0, $this->backgroundColor[0])
			);
		}

		if ($this->noise)
		{
			$this->whiteNoiseDensity = 1 / (int) $this->whiteNoiseDensity;
			$this->blackNoiseDensity = 1 / (int) $this->blackNoiseDensity;
		}
	}

	/**
	 * Generates keystring and image
	 *
	 * @return  boolean
	 *
	 * @throws  \Exception
	 * @since   2.0
	 */
	public function render(): bool
	{
		// Test if GD+ is loaded.
		if (!extension_loaded('gd'))
		{
			jexit('GD+ library is not available!');
		}

		$fonts            = array();
		$fontsdirAbsolute = Path::clean($this->fontsDir);

		if (!is_dir($fontsdirAbsolute))
		{
			// Try to find fonts in class folder.
			$fontsdirAbsolute = dirname(__FILE__) . '/fonts';
		}

		if ($handle = @opendir($fontsdirAbsolute))
		{
			while (false !== ($file = readdir($handle)))
			{
				if (preg_match('/\.png$/i', $file))
				{
					$fonts[] = Path::clean($fontsdirAbsolute . '/' . $file);
				}
			}

			closedir($handle);
		}
		else
		{
			jexit('Fonts not found.');
		}

		$alphabetLength = strlen($this->alphabet);

		do
		{
			// Generating random keystring
			while (true)
			{
				$this->keyString = '';

				for ($i = 0; $i < $this->length; $i++)
				{
					$this->keyString .= $this->allowedSymbols[mt_rand(0, strlen($this->allowedSymbols) - 1)];
				}

				if (!preg_match('/cp|cb|ck|c6|c9|rn|rm|mm|co|do|cl|db|qp|qb|dp|ww/', $this->keyString))
				{
					break;
				}
			}

			$fontFile = $fonts[mt_rand(0, count($fonts) - 1)];
			$font     = imagecreatefrompng($fontFile);
			imagealphablending($font, true);
			$fontfileWidth  = imagesx($font);
			$fontfileHeight = imagesy($font) - 1;
			$fontMetrics    = array();
			$symbol         = 0;
			$readingSymbol  = false;

			// Loading font
			for ($i = 0; $i < $fontfileWidth && $symbol < $alphabetLength; $i++)
			{
				$transparent = (imagecolorat($font, $i, 0) >> 24) == 127;

				if (!$readingSymbol && !$transparent)
				{
					$fontMetrics[$this->alphabet[$symbol]] = array('start' => $i);
					$readingSymbol                         = true;
					continue;
				}

				if ($readingSymbol && $transparent)
				{
					$fontMetrics[$this->alphabet[$symbol]]['end'] = $i;
					$readingSymbol                                = false;
					$symbol++;
				}
			}

			$img = imagecreatetruecolor($this->width, $this->height);
			imagealphablending($img, true);
			$white = imagecolorallocate($img, 255, 255, 255);
			imagefilledrectangle($img, 0, 0, $this->width - 1, $this->height - 1, $white);

			// Draw text
			$x = 1;
			$odd = mt_rand(0, 1);

			if ($odd == 0)
			{
				$odd = -1;
			}

			for ($i = 0; $i < $this->length; $i++)
			{
				$m = $fontMetrics[$this->keyString[$i]];
				$y = (($i % 2) * $this->fluctuationAmplitude - $this->fluctuationAmplitude / 2) * $odd
					+ mt_rand(-round($this->fluctuationAmplitude / 3), round($this->fluctuationAmplitude / 3))
					+ ($this->height - $fontfileHeight) / 2;

				if ($this->noSpaces)
				{
					$shift = 0;

					if ($i > 0)
					{
						$shift = 10000;

						for ($sy = 3; $sy < $fontfileHeight - 10; $sy += 1)
						{
							for ($sx = $m['start'] - 1; $sx < $m['end']; $sx += 1)
							{
								$rgb     = imagecolorat($font, $sx, $sy);
								$opacity = $rgb >> 24;

								if ($opacity < 127)
								{
									$left = $sx - $m['start'] + $x;
									$py   = $sy + $y;

									if ($py > $this->height)
									{
										break;
									}

									for ($px = min($left, $this->width - 1); $px > $left - 200 && $px >= 0; $px -= 1)
									{
										$color = imagecolorat($img, (int) $px, (int) $py) & 0xff;

										if ($color + $opacity < 170)
										{
											if ($shift > $left - $px)
											{
												$shift = $left - $px;
											}

											break;
										}
									}

									break;
								}
							}
						}

						if ($shift == 10000)
						{
							$shift = mt_rand(4, 6);
						}
					}
				}
				else
				{
					$shift = 1;
				}

				imagecopy($img, $font, intval($x - $shift), (int) $y, $m['start'], 1, $m['end'] - $m['start'], $fontfileHeight);
				$x += $m['end'] - $m['start'] - $shift;
			}
		}
		while ($x >= $this->width - 10);

		// Noise
		if ($this->noise)
		{
			$white = imagecolorallocate($font, 255, 255, 255);
			$black = imagecolorallocate($font, 0, 0, 0);

			for ($i = 0; $i < (($this->height - 30) * $x) * $this->whiteNoiseDensity; $i++)
			{
				imagesetpixel($img, mt_rand(0, $x - 1), mt_rand(10, $this->height - 15), $white);
			}

			for ($i = 0; $i < (($this->height - 30) * $x) * $this->blackNoiseDensity; $i++)
			{
				imagesetpixel($img, mt_rand(0, $x - 1), mt_rand(10, $this->height - 15), $black);
			}
		}

		$center = $x / 2;

		// Credits.
		$img2       = imagecreatetruecolor($this->width, $this->height + ($this->showCredits ? 12 : 0));
		$foreground = imagecolorallocate($img2, $this->foregroundColor[0], $this->foregroundColor[1], $this->foregroundColor[2]);
		$background = imagecolorallocate($img2, $this->backgroundColor[0], $this->backgroundColor[1], $this->backgroundColor[2]);
		imagefilledrectangle($img2, 0, 0, $this->width - 1, $this->height - 1, $background);
		imagefilledrectangle($img2, 0, $this->height, $this->width - 1, $this->height + 12, $foreground);
		$credits = empty($this->credits) ? $_SERVER['HTTP_HOST'] : $this->credits;
		imagestring($img2, 2, $this->width / 2 - imagefontwidth(2) * strlen($credits) / 2, $this->height - 2, $credits, $background);

		// Periods
		$rand1 = mt_rand(750000, 1200000) / 10000000;
		$rand2 = mt_rand(750000, 1200000) / 10000000;
		$rand3 = mt_rand(750000, 1200000) / 10000000;
		$rand4 = mt_rand(750000, 1200000) / 10000000;

		// Phases
		$rand5 = mt_rand(0, 31415926) / 10000000;
		$rand6 = mt_rand(0, 31415926) / 10000000;
		$rand7 = mt_rand(0, 31415926) / 10000000;
		$rand8 = mt_rand(0, 31415926) / 10000000;

		// Amplitudes
		$rand9  = mt_rand(330, 420) / 110;
		$rand10 = mt_rand(330, 450) / 100;

		// Wave distortion
		for ($x = 0; $x < $this->width; $x++)
		{
			for ($y = 0; $y < $this->height; $y++)
			{
				$sx = $x + (sin($x * $rand1 + $rand5) + sin($y * $rand3 + $rand6)) * $rand9 - $this->width / 2 + $center + 1;
				$sy = $y + (sin($x * $rand2 + $rand7) + sin($y * $rand4 + $rand8)) * $rand10;

				if ($sx < 0 || $sy < 0 || $sx >= $this->width - 1 || $sy >= $this->height - 1)
				{
					continue;
				}
				else
				{
					$color   = imagecolorat($img, (int) $sx, (int) $sy) & 0xFF;
					$colorX  = imagecolorat($img, (int) $sx + 1, (int) $sy) & 0xFF;
					$colorY  = imagecolorat($img, (int) $sx, (int) $sy + 1) & 0xFF;
					$colorXY = imagecolorat($img, (int) $sx + 1, (int) $sy + 1) & 0xFF;
				}

				if ($color == 255 && $colorX == 255 && $colorY == 255 && $colorXY == 255)
				{
					continue;
				}
				elseif ($color == 0 && $colorX == 0 && $colorY == 0 && $colorXY == 0)
				{
					$newred   = $this->foregroundColor[0];
					$newgreen = $this->foregroundColor[1];
					$newblue  = $this->foregroundColor[2];
				}
				else
				{
					$frsx  = $sx - floor($sx);
					$frsy  = $sy - floor($sy);
					$frsx1 = 1 - $frsx;
					$frsy1 = 1 - $frsy;

					$newcolor = (
						$color * $frsx1 * $frsy1 +
						$colorX * $frsx * $frsy1 +
						$colorY * $frsx1 * $frsy +
						$colorXY * $frsx * $frsy);

					if ($newcolor > 255)
					{
						$newcolor = 255;
					}

					$newcolor  = $newcolor / 255;
					$newcolor0 = 1 - $newcolor;

					$newred   = $newcolor0 * $this->foregroundColor[0] + $newcolor * $this->backgroundColor[0];
					$newgreen = $newcolor0 * $this->foregroundColor[1] + $newcolor * $this->backgroundColor[1];
					$newblue  = $newcolor0 * $this->foregroundColor[2] + $newcolor * $this->backgroundColor[2];
				}

				imagesetpixel($img2, $x, $y, imagecolorallocate($img2, (int) $newred, (int) $newgreen, (int) $newblue));
			}
		}

		/** @var \Joomla\CMS\Document\Document $document */
		// Joomla overrides mime type headers, so we must to use setMimeEncoding().
		$document = Factory::getApplication()->getDocument();

		header_remove('X-Powered-By');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', false);
		header('Pragma: no-cache');

		if (function_exists('imagejpeg'))
		{
			$document->setMimeEncoding('image/jpeg');

			return imagejpeg($img2, null, $this->jpegQuality);
		}
		elseif (function_exists('imagegif'))
		{
			$document->setMimeEncoding('image/gif');

			return imagegif($img2);
		}
		elseif (function_exists('imagepng'))
		{
			$document->setMimeEncoding('image/x-png');

			return imagepng($img2);
		}

		return false;
	}

	/**
	 * Returns keystring
	 *
	 * @return string
	 *
	 * @since  2.0
	 */
	public function getKeyString(): string
	{
		return $this->keyString;
	}

	/**
	 * Set a property value.
	 *
	 * @param   string  $property  Option key
	 * @param   mixed   $value     Key value
	 *
	 * @return  mixed  The value of the that has been set.
	 *
	 * @since  2.0
	 */
	public function set(string $property, $value)
	{
		// Do not set keystring via setter.
		if (strtolower($property) == 'keystring')
		{
			return $this->$property;
		}

		// Handle some properties which should be an array.
		if (preg_match('@^#@', $value))
		{
			$this->$property = self::hex2array($value);
		}
		elseif (strpos($value, 'rgb') !== false || strpos($value, 'rgba') !== false)
		{
			$this->$property = self::rgb2array($value);
		}
		else
		{
			$this->$property = $value;
		}

		return $this->$property;
	}

	/**
	 * Convert hex color(#ffffff) to rgb array.
	 *
	 * @param   string  $hex  Color code
	 *
	 * @return  array   Return array(0 - red, 1 - green, 2 - blue).
	 *
	 * @since   2.0
	 */
	public function hex2array(string $hex): array
	{
		$hex = str_replace('#', '', $hex);

		return array(
			base_convert(substr($hex, 0, 2), 16, 10),
			base_convert(substr($hex, 2, 2), 16, 10),
			base_convert(substr($hex, 4, 2), 16, 10)
		);
	}

	/**
	 * Convert rgb(180, 180, 180) to rgb array.
	 *
	 * @param   string  $rgb  Color code
	 *
	 * @return  array   Return array(0 - red, 1 - green, 2 - blue).
	 *
	 * @since   2.0
	 */
	public function rgb2array(string $rgb): array
	{
		$rgb = preg_replace('#rgb|rgba|\(|\)|\s+#', '', $rgb);

		return explode(',', $rgb);
	}
}
