<?php
/**
 * Changelog entry template
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2018 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\Deployer
 */

?>
## [<?php echo $changelog_entry->version; ?>] - <?php echo $changelog_entry->date->format( 'Y-m-d' ); ?>

<?php echo $changelog_entry->body; ?>

Full set of changes: [`<?php echo $changelog_entry->get_version_compare(); ?>`][<?php echo $changelog_entry->version; ?>]

[<?php echo $changelog_entry->version; ?>]: <?php echo $changelog_entry->get_link(); ?>
