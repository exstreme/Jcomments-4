<?php
/**
 * KCAPTCHA PROJECT
 * Automatic test to tell computers and humans apart.
 *
 * System requirements: PHP 4.0.6+ w/ GD
 *
 * @version           2.0.0
 * @package           KCAPTCHA
 * @author            Kruglov Sergei <kruglov@yandex.ru>
 * @copyright     (C) 2006-2008 Kruglov Sergei
 * @copyright     (C) 2021 Vladimir Globulopolis
 * @license           KCAPTCHA is a free software. You can freely use it for building own site or software.
 *                    If you use this software as a part of own sofware, you must leave copyright notices intact or add
 *                    KCAPTCHA copyright notices to own.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Filesystem\Path;

/**
 * @package  KCAPTCHA
 *
 * @since    1.0.0
 */
class KCAPTCHA
{
	protected $alphabet = "0123456789abcdefghijklmnopqrstuvwxyz";

	protected $allowedSymbols = "23456789abcdeghkmnpqsuvxyz";

	protected $fontsDir = 'fonts';

	protected $length = 5;

	protected $width = 121;

	protected $height = 60;

	protected $fluctuationAmplitude = 5;

	protected $noSpaces = true;

	protected $showCredits = false;

	protected $credits = '';

	protected $foregroundColor = array(180, 180, 180);

	protected $backgroundColor = array(246, 246, 246);

	protected $jpegQuality = 90;

	protected $keyString = null;

	/**
	 * Class constructor
	 *
	 * @param   array  $options  Array of options
	 *
	 * @since   2.0.0
	 */
	public function __construct($options = array())
	{
		if (is_array($options))
		{
			foreach ($options as $property => $value)
			{
				$this->set($property, $value);
			}
		}
	}

	/**
	 * Generates keystring and image
	 *
	 * @return  void
	 *
	 * @since   2.0.0
	 */
	public function render()
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

			for ($i = 0; $i < $this->length; $i++)
			{
				$m = $fontMetrics[$this->keyString[$i]];
				$y = mt_rand(-$this->fluctuationAmplitude, $this->fluctuationAmplitude) + ($this->height - $fontfileHeight) / 2 + 2;

				if ($this->noSpaces)
				{
					$shift = 0;

					if ($i > 0)
					{
						$shift = 10000;

						for ($sy = 7; $sy < $fontfileHeight - 20; $sy += 1)
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

									for ($px = min($left, $this->width - 1); $px > $left - 12 && $px >= 0; $px -= 1)
									{
										$color = imagecolorat($img, $px, $py) & 0xff;

										if ($color + $opacity < 190)
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

				imagecopy($img, $font, $x - $shift, $y, $m['start'], 1, $m['end'] - $m['start'], $fontfileHeight);
				$x += $m['end'] - $m['start'] - $shift;
			}
		} while ($x >= $this->width - 10);

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
		$rand10 = mt_rand(330, 450) / 110;

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
					$color   = imagecolorat($img, $sx, $sy) & 0xFF;
					$colorX  = imagecolorat($img, $sx + 1, $sy) & 0xFF;
					$colorY  = imagecolorat($img, $sx, $sy + 1) & 0xFF;
					$colorXY = imagecolorat($img, $sx + 1, $sy + 1) & 0xFF;
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

				imagesetpixel($img2, $x, $y, imagecolorallocate($img2, $newred, $newgreen, $newblue));
			}
		}

		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', false);
		header('Pragma: no-cache');

		if (function_exists("imagejpeg"))
		{
			header("Content-Type: image/jpeg");
			imagejpeg($img2, null, $this->jpegQuality);
		}
		elseif (function_exists("imagegif"))
		{
			header("Content-Type: image/gif");
			imagegif($img2);
		}
		elseif (function_exists("imagepng"))
		{
			header("Content-Type: image/x-png");
			imagepng($img2);
		}
	}

	/**
	 * Returns keystring
	 *
	 * @return string
	 *
	 * @since  2.0.0
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
	 * @since  2.0.0
	 */
	public function set($property, $value)
	{
		// Do not set keystring via setter.
		if (strtolower($property) == 'keystring')
		{
			return $this->$property;
		}

		// Handle some properties which should be an array.
		if ($property === 'foregroundColor' || $property === 'backgroundColor')
		{
			$_value = explode(',', $value);
			list($r, $g, $b) = $_value;
			$this->$property = array(
				trim((int) $r),
				trim((int) $g),
				trim((int) $b)
			);
		}
		else
		{
			$this->$property = $value;
		}

		return $this->$property;
	}
}
