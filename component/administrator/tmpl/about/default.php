<?php
/**
 * JComments - Joomla Comment System
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/** @var Joomla\Component\Jcomments\Administrator\View\About\HtmlView $this */

preg_match('(\d+\.\d+)', $this->component['version'], $matches);
?>
<div class="main-card">
	<div class="row">
		<div class="col-lg-1">
			<p style="margin: 1em;"><img src="<?php echo Uri::root(); ?>media/com_jcomments/images/icon-48-jcomments.jpg" alt="JComments"/></p>
		</div>
		<div class="col-lg-11">
			<table class="table">
				<tbody>
				<tr>
					<td>
						<span class="text-warning"><strong>JComments <?php echo $this->component['version']; ?></strong></span>
						<span class="text-warning">[<?php echo $this->component['creationDate']; ?>]</span>
						<br><br>
						<span>
							<?php echo Text::sprintf(
								'A_ABOUT_JCOMMENTS_GITHUB_PROJECT',
								$matches[0]
							); ?>
						</span>
						<div class="text-secondary"><?php echo Text::sprintf('A_ABOUT_COPYRIGHTS', date('Y')); ?></div>
					</td>
				</tr>
				<tr>
					<td>
						<h3><?php echo Text::_('A_ABOUT_TESTERS'); ?></h3>
						Dutch, b2z, zikkuratvk, ABTOP, SmokerMan, Arkadiy, Gruz, Efanych, Paul Geerlings
					</td>
				</tr>
				<tr>
					<td>
						<h3><?php echo Text::_('A_ABOUT_TRANSLATORS'); ?></h3>
						<ul>
							<li>Arabic - Ashraf Damra</li>
							<li>Belorussian - Samsonau Siarhei, Dmitry Tsesluk, Prywid</li>
							<li>Bengali (Bangladesh) - Nasir Khan Saikat</li>
							<li>Bosnian - Samir Gutu&#263;</li>
							<li>Bulgarian - Ana Vasileva, Alexander Sidorov, Georgi Gerov, Ivo Apostolov
							</li>
							<li>Catalan (Spain) - Xavier Montana Carreras</li>
							<li>Chinese - Yusuf Wang, moiska</li>
							<li>Croatian - Tomislav Kikic</li>
							<li>Czech - Ale&#353; Drnovsk&yacute;</li>
							<li>Danish - ot2sen, Martin Podolak, Mads</li>
							<li>Dutch - Aapje, Eleonora van Nieuwburg, Pieter Agten, Kaizer M. (Mirjam)</li>
							<li>English - Alexey Brin, ABTOP</li>
							<li>Estonian - Rivo Z&#228;ngov</li>
							<li>Finnish - Sami Haaranen (aka Mortti)</li>
							<li>French - Saber, Jean-Marie Chauvel, Eric Lamy, Max Schmit, Philippe (phnoel)</li>
							<li>Galician (Spain) - Manuel - Simboloxico Vol.2</li>
							<li>German - Denis Panschinski, Max Schmit, Hermann Herz</li>
							<li>Greek - Lazaros Giannakidis, Chrysovalantis Mochlas</li>
							<li>Hebrew - vollachr</li>
							<li>Hungarian - J&oacute;zsef Tam&aacute;s Herczeg</li>
							<li>Italian - Marco a.k.a. Vamba, Giuseppe Covino, Guido Romano</li>
							<li>Japanese - spursmusasi</li>
							<li>Khmer - Sovann Heng</li>
							<li>Latvian - Igors Maslakovs, Igor Vetruk, Dmitrijs Rekuns</li>
							<li>Lithuanian - Andrewas, abc123, Martynas</li>
							<li>Norwegian - Helge Johnsen, &Oslash;yvind S&oslash;nderbye</li>
							<li>Persian - hostkaran, ULTIMATE, Mahdi Ahazan (JoomlaFarsi.com)</li>
							<li>Polish - Tomasz Zi&oacute;&#322;czy&#324;ski, Jamniq, Sebastian Dajnowiec,
								Piotr
								Kwiatkowski, Stefan Wajda
							</li>
							<li>Portuguese (Portugal) - Paulo Izidoro, Pedro Jesus</li>
							<li>Portuguese (Brazil) - Daniel Gomes, Caio Guimaraes, Manoel Silva (iikozen),
								Washington
								Ribeiro
							</li>
							<li>Romanian - zlideni, Dan Partac, Razvan Ciule</li>
							<li>Serbian - Ivan Krkotic, Ivan Milosavljevic</li>
							<li>Slovak - Vladim&iacute;r Proch&aacute;zka</li>
							<li>Slovenian - Dorjano Baruca, Chico</li>
							<li>Spanish - Selim Alamo Bocaz, Miguel Tuyar&#233;</li>
							<li>Spanish (Argentina) - migueliyo17</li>
							<li>Swedish - MulletMidget</li>
							<li>Thai - Thammatorn Kraikokit, AriesAnywhere</li>
							<li>Turkish - Tolga Sanci, Aytug Halil AKAR</li>
							<li>Ukrainian - Denys Nosov, Yurii Smetana</li>
						</ul>
					</td>
				</tr>
				<tr>
					<td>
						<h3><?php echo Text::_('A_ABOUT_LOGO_DESIGN'); ?></h3>
						Dmitry Zuzin aka MrDenim
					</td>
				</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>
