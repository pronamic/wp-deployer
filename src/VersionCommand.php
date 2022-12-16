<?php
/**
 * Version command
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2018 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\Deployer
 */

namespace Pronamic\Deployer;

use Acme\Command\DefaultCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Version command
 *
 * @author  Remco Tolsma
 * @version 1.0.0
 * @since   1.0.0
 */
class VersionCommand extends Command {
	/**
	 * Configure.
	 */
	protected function configure() {
		$this
			->setName( 'version' )
			->setDescription( 'Version.' );
	}

	/**
	 * Execute.
	 */
	protected function execute( InputInterface $input, OutputInterface $output ) {
		$io = new SymfonyStyle( $input, $output );

		$process_helper = $this->getHelper( 'process' );

		$filesystem = new Filesystem();

		$cwd = \getcwd();

		$composer_json_file = $cwd . '/composer.json';
		$package_json_file  = $cwd . '/package.json';

		$io->title( 'Pronamic Deployer version' );

		/**
		 * Git pull.
		 * 
		 * @link https://git-scm.com/docs/git-pull
		 */
		$process = new Process( 'git pull', $cwd );

		$process_helper->mustRun( $output, $process );

		/**
		 * Git status.
		 * 
		 * @link https://git-scm.com/docs/git-status
		 * @link https://unix.stackexchange.com/questions/155046/determine-if-git-working-directory-is-clean-from-a-script
		 */
		$process = new Process( 'git status --porcelain', $cwd );

		$process_helper->mustRun( $output, $process );

		$git_status = $process->getOutput();

		if ( '' !== $git_status ) {
			$io->text( $git_status );

			$io->error( 'Working tree status not empty (`git status`).' );

			return 1;
		}

		/**
		 * Git branch.
		 * 
		 * @link https://git-scm.com/docs/git-branch
		 * @link https://stackoverflow.com/questions/6245570/how-do-i-get-the-current-branch-name-in-git
		 */
		$process = new Process( 'git branch --show-current', $cwd );

		$process_helper->mustRun( $output, $process );

		$branch = trim( $process->getOutput() );

		/**
		 * Detect type.
		 * 
		 * @link https://getcomposer.org/doc/04-schema.md#type
		 * @link https://www.smashingmagazine.com/2019/03/composer-wordpress/
		 * @link https://easyengine.io/tutorials/composer-wordpress/manage-themes-plugins/
		 */
		$type = '';

		if ( is_readable( $composer_json_file ) ) {
			$data = file_get_contents( $composer_json_file );

			$composer_json = json_decode( $data );

			if ( ! property_exists( $composer_json, 'type' ) ) {
				$io->note( 'The `composer.json` file is missing a `type` property.' );
			}

			if ( property_exists( $composer_json, 'type' ) ) {
				$type = $composer_json->type;
			}
		}

		/**
		 * Detect version.
		 */
		$version = '';

		if ( is_readable( $package_json_file ) ) {
			$data = file_get_contents( $package_json_file );

			$package_json = json_decode( $data );

			if ( ! property_exists( $package_json, 'version' ) ) {
				$io->note( 'The `package.json` file is missing a `version` property.' );
			}

			if ( property_exists( $package_json, 'version' ) ) {
				$version = $package_json->version;
			}
		}

		$file_headers = new FileHeaders();

		/**
		 * If type = wordpress-plugin update plugin file.
		 * How do we find the main plugin file?
		 * Check of version header value exists?
		 * 
		 * @link https://github.com/WordPress/gutenberg/search?q=pluginEntryPoint
		 * @link https://docs.npmjs.com/cli/v8/configuring-npm/package-json#man
		 * @link https://developer.wordpress.org/reference/functions/get_plugins/
		 * @link https://developer.wordpress.org/reference/functions/get_plugin_data/
		 * @link https://developer.wordpress.org/reference/functions/get_file_data/
		 */
		$plugins = [];

		if ( \in_array( $type, [ '', 'wordpress-plugin' ], true ) ) {
			foreach ( \glob( $cwd . '/*.php' ) as $file ) {
				$headers = $file_headers->get_headers( $file );

				if ( \array_key_exists( 'Plugin Name', $headers ) ) {
					$plugins[ $file ] = $headers;
				}
			}
		}

		/**
		 * Multiple plugins is not recommended.
		 * 
		 * Only one file in the plugin’s folder should have the header
		 * comment — if the plugin has multiple PHP files, only one of
		 * those files should have the header comment.
		 * 
		 * @link https://developer.wordpress.org/plugins/plugin-basics/
		 */
		if ( count( $plugins ) > 1 ) {
			$io->note( 'Found multiple plugins, only one file in the plugin’s folder should have the header comment.' );
		}

		foreach ( $plugins as $file => $headers ) {
			if ( \array_key_exists( 'Version', $headers ) ) {
				$version = $headers['Version'];
			}
		}

		$io->title( 'Version' );

		$io->table(
			[
				'Key',
				'Value',
			],
			[
				[ 'Directory', $cwd ],
				[ 'Branch', $branch ],
				[ 'Type', $type ],
				[ 'Version', $version ],
			]
		);

		/**
		 * New version.
		 * 
		 * @link https://docs.npmjs.com/cli/v8/commands/npm-version#description
		 * @link https://github.com/npm/node-semver#functions
		 */
		$bump_method = $io->choice(
			'Select bump methpd',
			[
				// 'input',
				'major',
				'minor',
				'patch',
			// 'premajor',
			// 'preminor',
			// 'prepatch',
			// 'prerelease',
			// 'from-git',
			],
			'patch' 
		);

		$semver = new SemanticVersion( $version );

		switch ( $bump_method ) {
			case 'input':
				$new_version = $io->ask( 'New version?' );

				break;
			case 'major':
			case 'minor':
			case 'patch':
				$new_version = $semver->inc( $bump_method );

				break;
			case 'premajor':
				$io->error( 'Bump method `premajor` not implemented.' );

				return 1;
			case 'preminor':
				$io->error( 'Bump method `preminor` not implemented.' );

				return 1;
			case 'prepatch':
				$io->error( 'Bump method `prepatch` not implemented.' );

				return 1;
			case 'prerelease':
				$io->error( 'Bump method `prerelease` not implemented.' );

				return 1;
			case 'from-git':
				$io->error( 'Bump method `from-git` not implemented.' );

				return 1;
			default:
				$new_version = $bump_method;

				break;
		}

		$io->section( 'Details' );

		$io->table(
			[
				'Key',
				'Value',
			],
			[
				[ 'Working Directory', $cwd ],
				[ 'Type', $type ],
				[ 'Version', $version ],
				[ 'New Version', $new_version ],
			]
		);

		/**
		 * If CHANGELOG.md check if new version is part of it?
		 */
		$file_changelog_md = $cwd . '/CHANGELOG.md';

		if ( ! is_readable( $file_changelog_md ) ) {
			$io->note( 'It is a good idea to keep track of the changes in a `CHANGELOG.md` file: https://keepachangelog.com/.' );
		}

		if ( is_readable( $file_changelog_md ) ) {
			/**
			 * Remote URL.
			 * 
			 * @link https://github.com/cookpete/auto-changelog/blob/0991f17ce936a9db490e2ad1a04121755038b78d/src/remote.js
			 */
			$process = new Process( 'git remote get-url origin', $cwd );

			$process_helper->mustRun( $output, $process );

			$url = $process->getOutput();

			$components = $this->parse_git_url( $url );

			$url_repository = 'https://' . $components['host'] . '/' . $components['organisation'] . '/' . $components['repository'];

			$changelog = new Changelog( $file_changelog_md );

			if ( ! $changelog->has_entry( $new_version ) ) {
				$changelog_entry = $changelog->new_entry( $new_version );

				$changelog_entry->url = $url_repository;

				$changelog_entry->version_previous = $version;

				$changelog_entry->body .= $this->add_git_log( $cwd, $version, $output );

				if ( isset( $composer_json ) ) {
					$changelog_entry->body .= $this->add_composer_updates( $cwd, $version, $output );
				}

				$process = new Process( 'subl -', null, null, $changelog_entry->body );

				$process_helper->mustRun( $output, $process );

				$changelog_entry->body = $process->getOutput();

				$changelog_entry_string = trim( $changelog_entry->render() ) . "\n\n";

				$changelog->insert( $changelog->get_insert_position(), $changelog_entry_string );

				$changelog->save();
			}
		}

		/**
		 * Plugins.
		 */
		foreach ( $plugins as $file => $headers ) {
			$file_headers->set_headers(
				$file,
				[
					'Version' => $new_version,
				]
			);
		}

		/**
		 * If type = wordpress-theme update style.css file.
		 *
		 * Check of version header value exists?
		 */
		$file_style = $cwd . '/style.css';

		if ( 'wordpress-theme' === $type ) {
			if ( ! is_readable( $file_style ) ) {
				$io->note( 'The `style.css` file is missing.' );
			}
		}

		if ( \in_array( $type, [ '', 'wordpress-theme' ], true ) ) {
			if ( is_readable( $file_style ) ) {
				$headers = $file_headers->get_headers( $file_style );

				if ( ! \array_key_exists( 'Theme Name', $headers ) ) {
					$io->note( 'The `Theme Name` header is missing in the `style.css` file.' );
				}

				if ( ! \array_key_exists( 'Version', $headers ) ) {
					$io->note( 'The `Version` header is missing in the `style.css` file.' );
				}

				$file_headers->set_headers(
					$file_style,
					[
						'Version' => $new_version,
					]
				);
			}
		}

		/**
		 * If type = wordpress-plugin or type = wordpress-theme
		 * and readme.txt exists patch readme.txt.
		 * 
		 * Check if "Stable tag" exists?
		 */
		$file_readme_txt = $cwd . '/readme.txt';

		if ( is_readable( $file_readme_txt ) ) {
			$headers = $file_headers->get_headers( $file_readme_txt );

			if ( ! \array_key_exists( 'Stable tag', $headers ) ) {
				$io->note( 'The `Stable tag` header is missing in the `readme.txt` file.' );
			}

			/**
			 * The 'Stable tag' only needs to be updated after tagging
			 * within the WordPress.org subversion repository.
			 */
			$file_headers->set_headers(
				$file_readme_txt,
				[
					'Stable tag' => $new_version,
				]
			);
		}

		/**
		 * If changelog is missing add commits / pull requests to CHANGELOG.md?
		 * 
		 * Ask user to manual check the changelog?
		 * 
		 * Confirm the changelog.
		 */

		/**
		 * If readme.txt check if new version is in changelog section?
		 */

		/**
		 * If changelog is missing patch it from CHANGELOG.md to readm.txt.
		 * 
		 * Confirm the changelog.
		 */

		/**
		 * Check if "Requires PHP" matches composer PHP requrement.
		 * 
		 * @link https://mikemadison.net/blog/2020/11/17/configuring-php-version-with-composer
		 */

		/**
		 * If package.json exists use `npm version`?
		 * Or `npm pkg set $new_version`?
		 */
		$file_package_json = $cwd . '/package.json';

		if ( is_readable( $file_package_json ) ) {
			$command = sprintf(
				'npm pkg set version=%s',
				$new_version
			);

			$process = new Process( $command );

			$process_helper->mustRun( $output, $process );
		}

		/**
		 * GitHub CLI, create concept release?
		 * 
		 * @link https://cli.github.com/
		 */
	}

	/**
	 * Parse git URL.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private function parse_git_url( $url ) {
		/**
		 * Parse GitHub SSH notation.
		 * 
		 * git@github.com:organisation/repository.git
		 */
		if ( str_starts_with( $url, 'git@github.com:' ) ) {
			$user         = strtok( $url, '@' );
			$host         = strtok( ':' );
			$organisation = strtok( '/' );
			$repository   = strtok( '.git' );

			return [
				'user'         => $user,
				'host'         => $host,
				'organisation' => $organisation,
				'repository'   => $repository,
			];
		}

		/**
		 * Parse URL.
		 *
		 * @link https://github.com/jonschlinkert/parse-github-url
		 */
		$components = \parse_url( $url );

		$host = $components['host'];

		$path = $components['path'];

		$organisation = strtok( $path, '/' );
		$repository   = strtok( '.' );

		return [
			'user'         => '',
			'host'         => $host,
			'organisation' => $organisation,
			'repository'   => $repository,
		];
	}

	public function change_present_to_past_tense( $text ) {
		$patterns = [
			'add'    => '/^Add /',
			'create' => '/^Create /',
			'fix'    => '/^Fix /',
			'update' => '/^Update /',
			'remove' => '/^Remove /',
		];

		$replacements = [
			'add'    => 'Added ',
			'create' => 'Created ',
			'fix'    => 'Fixed ',
			'update' => 'Updated ',
			'remove' => 'Removed ',
		];

		return \preg_replace( $patterns, $replacements, $text );
	}

	public function add_git_log( $cwd, $version, $output ) {
		$process_helper = $this->getHelper( 'process' );

		/**
		 * @link https://git-scm.com/docs/pretty-formats
		 * @link https://git-scm.com/book/en/v2/Git-Basics-Viewing-the-Commit-History#pretty_format
		 * @link https://git-scm.com/book/en/v2/Git-Basics-Viewing-the-Commit-History
		 * @link https://github.com/cookpete/auto-changelog#custom-templates
		 */
		$command = 'git --no-pager log --pretty=oneline tags/' . $version . '..HEAD';

		$process = new Process( $command, $cwd );

		$process_helper->run( $output, $process );

		if ( ! $process->isSuccessful() ) {
			return '';
		}

		$git_log = $process->getOutput();

		$lines = explode( "\n", trim( $git_log ) );

		$commits = [];

		foreach ( $lines as $line ) {
			$hash       = substr( $line, 0, 40 );
			$title_line = substr( $line, 40 + 1 );

			$commit             = new GitCommit( $hash );
			$commit->title_line = $title_line;

			$commits[] = $commit;
		}

		$content = "\n";

		$content .= '### Commits' . "\n";

		$content .= "\n";

		foreach ( $commits as $commit ) {
			$content .= '- ' . $this->change_present_to_past_tense( $commit->title_line ) . "\n";
		}

		return $content;
	}

	public function add_composer_updates( $cwd, $version, $output ) {
		$composer_json_file = $cwd . '/composer.json';

		if ( ! is_readable( $composer_json_file ) ) {
			return '';
		}

		$data = file_get_contents( $composer_json_file );

		$composer_json = json_decode( $data );

		if ( ! is_object( $composer_json ) ) {
			return '';
		}

		if ( ! \property_exists( $composer_json, 'require' ) ) {
			return '';
		}

		$process_helper = $this->getHelper( 'process' );

		$object = 'tags/' . $version . ':composer.lock';

		$process = new Process( 'git show ' . $object, $cwd );

		$process_helper->run( $output, $process );

		$composer_lock_old = json_decode( $process->getOutput() );
		$composer_lock_new = json_decode( file_get_contents( $cwd . '/composer.lock' ) );

		if ( ! is_object( $composer_lock_old ) ) {
			return '';
		}

		if ( ! is_object( $composer_lock_new ) ) {
			return '';
		}

		$map_old = [];
		$map_new = [];

		foreach ( $composer_lock_old->packages as $package ) {
			$map_old[ $package->name ] = $package->version;
		}

		foreach ( $composer_lock_new->packages as $package ) {
			$map_new[ $package->name ] = $package->version;
		}

		$content = "\n";

		$content .= '### Composer' . "\n";

		$content .= "\n";

		foreach ( $composer_json->require as $key => $value ) {
			if ( ! array_key_exists( $key, $map_old ) ) {
				continue;
			}

			if ( ! array_key_exists( $key, $map_new ) ) {
				continue;
			}

			$version_old = $map_old[ $key ];
			$version_new = $map_new[ $key ];

			if ( $version_old !== $version_new ) {
				$content .= \sprintf( 'Updated `%s` from `%s` to `%s`.', $key, $version_old, $version_new ) . PHP_EOL;
			}
		}

		return $content;
	}
}
