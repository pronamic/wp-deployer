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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
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
	 * New process.
	 * 
	 * @param string      $command Command.
	 * @param string|null $cwd     Directory.
	 * @param string|null $env     Environment.
	 * @param string|null $input   Input.
	 * @param string|null $timeout Timout.
	 * @return Process
	 */
	private function new_process( $command, $cwd = null, $env = null, $input = null, $timeout = null ) {
		$process = Process::fromShellCommandline( $command, $cwd, $env, $input, $timeout );

		return $process;
	}

	/**
	 * Execute.
	 *
	 * @param InputInterface  $input  Input interface.
	 * @param OutputInterface $output Output interface.
	 * @return int
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
		$process = $this->new_process( 'git pull', $cwd );

		$process_helper->mustRun( $output, $process );

		/**
		 * Git status.
		 */
		$result = $this->check_working_directory_git_status( $cwd, $input, $output );

		if ( false === $result ) {
			return 1;
		}

		/**
		 * Git branch.
		 * 
		 * @link https://git-scm.com/docs/git-branch
		 * @link https://stackoverflow.com/questions/6245570/how-do-i-get-the-current-branch-name-in-git
		 */
		$process = $this->new_process( 'git branch --show-current', $cwd );

		$process_helper->mustRun( $output, $process );

		$branch = trim( $process->getOutput() );

		/**
		 * Git tag list.
		 * 
		 * @link https://git-scm.com/docs/git-branch
		 * @link https://stackoverflow.com/questions/6245570/how-do-i-get-the-current-branch-name-in-git
		 */
		$result = $this->check_git_tagnames( $cwd, $input, $output );

		if ( false === $result ) {
			return 1;
		}

		/**
		 * Composer script `preversion`.
		 * 
		 * @link https://github.com/pronamic/wp-deployer/issues/5
		 */
		$this->run_composer_script( 'preversion', $cwd, $output );

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

			$result = $this->check_composer_outdated( $cwd, $input, $output );

			if ( false === $result ) {
				return 1;
			}

			$result = $this->check_composer_non_comparable_versions( $cwd, $io );

			if ( false === $result ) {
				return 1;
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
		$semver = new SemanticVersion( $version );

		$bump_method = $io->choice(
			'Select bump method',
			[
				'major' => \sprintf( 
					'Major: from %s to %s.',
					'<info>' . $version . '</info>',
					'<info>' . $semver->inc( 'major' ) . '</info>'
				),
				'minor' => \sprintf( 
					'Minor: from %s to %s.',
					'<info>' . $version . '</info>',
					'<info>' . $semver->inc( 'minor' ) . '</info>'
				),
				'patch' => \sprintf( 
					'Patch: from %s to %s.',
					'<info>' . $version . '</info>',
					'<info>' . $semver->inc( 'patch' ) . '</info>'
				),
			]
		);

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
				[ 'Directory', $cwd ],
				[ 'Branch', $branch ],
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
			$process = $this->new_process( 'git remote get-url origin', $cwd );

			$process_helper->mustRun( $output, $process );

			$url = $process->getOutput();

			$components = $this->parse_git_url( $url );

			$url_repository = 'https://' . $components['host'] . '/' . $components['organisation'] . '/' . $components['repository'];

			$changelog = new Changelog( $file_changelog_md );

			if ( ! $changelog->has_entry( $new_version ) ) {
				$changelog_entry = $changelog->new_entry( $new_version );

				$changelog_entry->url = $url_repository;

				$changelog_entry->version_previous = $version;

				$changelog_entry->body .= $this->add_git_log( $cwd, $version, $output, $url_repository );

				if ( isset( $composer_json ) ) {
					$changelog_entry->body .= $this->generate_composer_json_changelog( $cwd, $version, $output );
				}

				$choice    = '';
				$iteration = 0;

				while ( 'ok' !== $choice ) {
					$iteration++;

					$io->title( 'Changelog' );

					$io->text( $changelog_entry->body );

					$choice = $io->choice(
						'🔁 ' . $iteration . ': Is the above changelog OK?',
						[
							'ok',
							'subl',
						],
						'subl' 
					);

					if ( 'subl' === $choice ) {
						$process = $this->new_process( 'subl -', $cwd, null, $changelog_entry->body );

						$process_helper->mustRun( $output, $process );

						$changelog_entry->body = $process->getOutput();
					}
				}

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
		 * If changelog is missing patch it from CHANGELOG.md to readme.txt.
		 * 
		 * Confirm the changelog.
		 */

		/**
		 * Check if "Requires PHP" matches composer PHP requirement.
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

			$process = $this->new_process( $command, $cwd );

			$process_helper->mustRun( $output, $process );
		}

		/**
		 * Composer script `version`.
		 * 
		 * @link https://github.com/pronamic/wp-deployer/issues/5
		 */
		$this->run_composer_script( 'version', $cwd, $output );

		/**
		 * Git commit.
		 * 
		 * @link https://github.com/npm/cli/blob/7018b3d46e10ea4d9d81a478dbdf114b6505ed36/workspaces/libnpmversion/lib/index.js#L17
		 * @link https://git-scm.com/docs/git-commit
		 */
		$message = \sprintf(
			'v%s',
			$new_version
		);

		$command = \sprintf(
			'git commit --all -m %s',
			\escapeshellarg( $message )
		);

		$process = $this->new_process( $command, $cwd );

		$process_helper->mustRun( $output, $process );

		/**
		 * Git tag.
		 * 
		 * @link https://github.com/npm/cli/blob/7018b3d46e10ea4d9d81a478dbdf114b6505ed36/workspaces/libnpmversion/lib/tag.js
		 * @link https://git-scm.com/docs/git-tag
		 */
		$tagname = \sprintf(
			'v%s',
			$new_version
		);

		$message = \sprintf(
			'v%s',
			$new_version
		);

		$command = \sprintf(
			'git tag -m %s %s',
			\escapeshellarg( $message ),
			\escapeshellarg( $tagname )
		);

		$process = $this->new_process( $command, $cwd );

		$process_helper->mustRun( $output, $process );

		/**
		 * Git push.
		 *
		 * @link https://docs.npmjs.com/cli/v7/commands/npm-version
		 */
		$should_push = $io->confirm( 'Do you want to push the changes and tag?', true );

		if ( ! $should_push ) {
			return 0;
		}

		$process = $this->new_process( 'git push', $cwd );

		$process_helper->mustRun( $output, $process );

		$process = $this->new_process( 'git push origin refs/tags/' . $tagname, $cwd );

		$process_helper->mustRun( $output, $process );

		/**
		 * GitHub release.
		 * 
		 * @link https://cli.github.com/manual/gh_release_create
		 */
		$should_create_gh_release = $io->confirm( 'Do you want to create a GitHub release? (deprecated)', false );

		if ( $should_create_gh_release ) {
			$io->title( 'GitHub release' );

			$assets = \array_map(
				'\escapeshellarg',
				\glob( $cwd . '/build/*.zip' )
			);

			$command = \sprintf(
				'gh release create %s --title %s --notes-file - %s',
				$tagname,
				\escapeshellarg( $new_version ),
				\implode( ' ', $assets )
			);

			$io->text( '<info>' . $command . '</info>' );

			$process = $this->new_process( $command, $cwd, null, $changelog_entry->body );

			$process_helper->mustRun( $output, $process );

			$io->write( $process->getOutput() );
		}

		/**
		 * Composer script `postversion`.
		 * 
		 * @link https://github.com/pronamic/wp-deployer/issues/5
		 */
		$this->run_composer_script( 'postversion', $cwd, $output );

		return 0;
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
		 * Example: `git@github.com:organisation/repository.git`
		 */
		if ( str_starts_with( $url, 'git@github.com:' ) ) {
			$user         = strtok( $url, '@' );
			$host         = strtok( ':' );
			$organisation = strtok( '/' );
			$repository   = strtok( '.' );

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

	/**
	 * Change present to past tense.
	 * 
	 * @param string $text Text.
	 * @return string
	 */
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

	/**
	 * Add Git log.
	 * 
	 * @param string          $cwd     Directory.
	 * @param string          $version Version.
	 * @param OutputInterface $output  Output interface.
	 * @param string          $url     URL.
	 * @return bool
	 */
	public function add_git_log( $cwd, $version, $output, $url ) {
		$process_helper = $this->getHelper( 'process' );

		/**
		 * Git log.
		 *
		 * @link https://git-scm.com/docs/pretty-formats
		 * @link https://git-scm.com/book/en/v2/Git-Basics-Viewing-the-Commit-History#pretty_format
		 * @link https://git-scm.com/book/en/v2/Git-Basics-Viewing-the-Commit-History
		 * @link https://github.com/cookpete/auto-changelog#custom-templates
		 */
		$command = 'git --no-pager log --pretty=oneline tags/v' . $version . '..HEAD';

		$process = $this->new_process( $command, $cwd );

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
			$commit_url = $url . '/commit/' . $commit->hash;

			$content .= '- ';
			$content .= $this->change_present_to_past_tense( $commit->title_line );
			$content .= ' ([' . substr( $commit->hash, 0, 7 ) . '](' . $commit_url . '))';
			$content .= "\n";
		}

		return $content;
	}

	/**
	 * Check working directory Git status.
	 * 
	 * @param string          $cwd    Directory.
	 * @param InputInterface  $input  Input interface.
	 * @param OutputInterface $output Output interface.
	 * @return bool
	 */
	private function check_working_directory_git_status( $cwd, $input, $output ) {
		$io = new SymfonyStyle( $input, $output );

		$process_helper = $this->getHelper( 'process' );

		/**
		 * Git status.
		 * 
		 * @link https://git-scm.com/docs/git-status
		 * @link https://unix.stackexchange.com/questions/155046/determine-if-git-working-directory-is-clean-from-a-script
		 * @link https://github.com/npm/cli/blob/7018b3d46e10ea4d9d81a478dbdf114b6505ed36/workspaces/libnpmversion/lib/enforce-clean.js
		 */
		$process = $this->new_process( 'git status --porcelain', $cwd );

		$process_helper->mustRun( $output, $process );

		$git_status = $process->getOutput();

		if ( '' !== $git_status ) {
			$io->text( $git_status );

			$io->note( 'Working tree status not empty (`git status`).' );

			return $io->confirm( 'Continue with open working tree?', false );
		}

		return true;
	}

	/**
	 * Check Git tag names.
	 *
	 * @param string          $cwd    Directory.
	 * @param InputInterface  $input  Input interface.
	 * @param OutputInterface $output Output interface.
	 * @return bool
	 */
	private function check_git_tagnames( $cwd, $input, $output ) {
		$io = new SymfonyStyle( $input, $output );

		$process_helper = $this->getHelper( 'process' );

		$process = $this->new_process( 'git --no-pager tag --list', $cwd );

		$process_helper->mustRun( $output, $process );

		$tag_list = \explode( "\n", \trim( $process->getOutput() ) );

		$tag_list_no_prefix = array_filter(
			$tag_list,
			function( $tagname ) {
				if ( 
					\in_array(
						$tagname,
						[
							'nightly',
						],
						true
					)
				) {
					return false;
				}

				return ! \str_starts_with( $tagname, 'v' );
			}
		);

		if ( count( $tag_list_no_prefix ) > 0 ) {
			$io->listing( $tag_list_no_prefix );

			$io->note( 'Detected tagnames without `v` prefix.' );

			$choice = $io->choice(
				'How to proceed?',
				[
					'exit',
					'ignore',
					'rename',
				],
				'exit' 
			);

			switch ( $choice ) {
				case 'ignore':
					return true;
				case 'rename':
					foreach ( $tag_list_no_prefix as $tagname ) {
						$tag_old = $tagname;
						$tag_new = 'v' . $tagname;

						// Tag new.
						$command = \sprintf(
							'git tag %s %s',
							$tag_new,
							$tag_old
						);

						$process = $this->new_process( $command, $cwd );

						$process_helper->mustRun( $output, $process );

						// Tag delete.
						$command = \sprintf(
							'git tag --delete %s',
							$tag_old
						);

						$process = $this->new_process( $command, $cwd );

						$process_helper->mustRun( $output, $process );
					}

					$command = \sprintf(
						'git push --tags --prune origin %s',
						\escapeshellarg( 'refs/tags/*' )
					);

					$process = $this->new_process( $command, $cwd );

					$process_helper->mustRun( $output, $process );

					// GitHub release edit (optional).
					foreach ( $tag_list_no_prefix as $tagname ) {
						$command = \sprintf(
							'gh release edit %s --tag %s',
							$tagname,
							'v' . $tagname
						);

						$process = $this->new_process( $command, $cwd );

						$process_helper->run( $output, $process );
					}

					return true;
				case 'exit':
				default:
					return false;
			}
		}

		return true;
	}

	/**
	 * Check Composer outdated.
	 * 
	 * @param string          $cwd    Directory.
	 * @param InputInterface  $input  Input interface.
	 * @param OutputInterface $output Output interface.
	 * @return bool
	 * @throws \Exception When `compsoer outdated` output is not JSON or an empty array.
	 */
	private function check_composer_outdated( $cwd, $input, $output ) {
		$io = new SymfonyStyle( $input, $output );

		$process_helper = $this->getHelper( 'process' );

		$command = 'composer outdated --direct --format json';

		$process = $this->new_process( $command, $cwd );

		$process_helper->mustRun( $output, $process );

		$result = json_decode( $process->getOutput() );

		if ( null === $result ) {
			throw new \Exception( \sprintf( 'Unexpected response from `%s`.', $command ) );
		}

		if ( [] === $result ) {
			throw new \Exception( 'No dependencies installed. Try running composer install or update.' );
		}

		if ( count( $result->installed ) > 0 ) {
			$process = $this->new_process( 'composer outdated', $cwd );
			$process->setTty( true );
			$process->mustRun();

			$io->write( $process->getOutput() );

			return $io->confirm( 'Continue with outdated Composer packages?', false );
		}

		return true;
	}

	/**
	 * Check Composer non-comparable version.
	 * 
	 * @param string       $cwd Directory.
	 * @param SymfonyStyle $io  Input/output style.
	 * @return bool
	 */
	private function check_composer_non_comparable_versions( $cwd, $io ) {
		$composer_json_file = $cwd . '/composer.json';
		$composer_lock_file = $cwd . '/composer.lock';

		if ( ! is_readable( $composer_json_file ) ) {
			return true;
		}

		if ( ! is_readable( $composer_lock_file ) ) {
			return true;
		}

		$composer_json = json_decode( file_get_contents( $composer_json_file ) );
		$composer_lock = json_decode( file_get_contents( $composer_lock_file ) );

		$packages = [];

		foreach ( $composer_lock->packages as $package ) {
			if ( ! property_exists( $composer_json->require, $package->name ) ) {
				continue;
			}

			if ( str_starts_with( $package->version, 'dev-' ) ) {
				$packages[] = $package;
			}
		}

		if ( count( $packages ) > 0 ) {
			$io->note( 'Detected non-comparable Composer package versions.' );

			$io->table(
				[
					'Package',
					'Version',
				],
				array_map(
					function( $package ) {
						return [
							$package->name,
							$package->version,
						];
					},
					$packages
				)
			);

			return $io->confirm( 'Continue with non comparable Composer package versions?', false );
		}

		return true;
	}

	/**
	 * Get cmoposer.lock packages map.
	 * 
	 * @param string $json JSON.
	 * @return array
	 */
	private function get_composer_lock_packages( $json ) {
		$data = json_decode( $json );

		if ( ! is_object( $data ) ) {
			return [];
		}

		if ( ! property_exists( $data, 'packages' ) ) {
			return [];
		}

		$map = [];

		foreach ( $data->packages as $package ) {
			$map[ $package->name ] = $package;
		}

		return $map;
	}

	/**
	 * Add composer updates.
	 * 
	 * @link https://getcomposer.org/doc/articles/versions.md#exact-version-constraint
	 * @param string          $cwd     Directory.
	 * @param string          $version Version.
	 * @param OutputInterface $output  Output interface.
	 * @return string
	 */
	public function generate_composer_json_changelog( $cwd, $version, $output ) {
		$lines = [];

		$file = 'composer.json';

		$process_helper = $this->getHelper( 'process' );

		$tagname = 'v' . $version;

		$object = 'tags/' . $tagname . ':' . $file;

		$process = $this->new_process( 'git show ' . $object, $cwd );

		$process_helper->run( $output, $process );

		$composer_json_old = json_decode( $process->getOutput() );
		$composer_json_new = json_decode( file_get_contents( $cwd . '/' . $file ) );

		if ( ! is_object( $composer_json_old ) ) {
			return [];
		}

		if ( ! is_object( $composer_json_new ) ) {
			return [];
		}

		// Composer `composer.lock`.
		$file = 'composer.lock';

		$object = 'tags/' . $tagname . ':' . $file;

		$process = $this->new_process( 'git show ' . $object, $cwd );

		$process_helper->run( $output, $process );

		$lock_old = $this->get_composer_lock_packages( $process->getOutput() );
		$lock_new = $this->get_composer_lock_packages( file_get_contents( $cwd . '/' . $file ) );

		$require_old = [];
		$require_new = [];

		if ( property_exists( $composer_json_old, 'require' ) ) {
			$require_old = (array) $composer_json_old->require;
		}

		if ( property_exists( $composer_json_new, 'require' ) ) {
			$require_new = (array) $composer_json_new->require;
		}

		$removed = array_diff_key( $require_old, $require_new );

		foreach ( $removed as $key => $version_constraint ) {
			$lines[ $key ] = \sprintf(
				'- Removed `%s` `%s`.',
				$key,
				$version_constraint
			);
		}

		$added = array_diff_key( $require_new, $require_old );

		foreach ( $added as $key => $version_constraint ) {
			$lines[ $key ] = \sprintf(
				'- Added `%s` `%s`.',
				$key,
				$version_constraint
			);
		}

		$changed = array_intersect_key( $require_new, $require_old );

		foreach ( $changed as $key => $version_constraint ) {
			$composer_json_old = $require_old[ $key ];
			$composer_json_new = $require_new[ $key ];

			$composer_lock_old = null;
			$composer_lock_new = null;

			if ( array_key_exists( $key, $lock_old ) ) {
				$composer_lock_old = $lock_old[ $key ]->version;
			}

			if ( array_key_exists( $key, $lock_new ) ) {
				$composer_lock_new = $lock_new[ $key ]->version;
			}

			$is_composer_json_changed = ( $composer_json_old !== $composer_json_new );
			$is_composer_lock_changed = ( $composer_lock_old !== $composer_lock_new && null !== $composer_lock_old && null !== $composer_lock_new );

			if ( $is_composer_json_changed || $is_composer_lock_changed ) {
				$content = \sprintf(
					'- Changed `%s` from `%s` to `%s`.',
					$key,
					$composer_lock_old ?? $composer_json_old,
					$composer_lock_new ?? $composer_json_new
				);

				if ( array_key_exists( $key, $lock_new ) ) {
					$package = $lock_new[ $key ];

					if ( str_contains( $package->source->url, 'github.com' ) ) {
						$components = $this->parse_git_url( $package->source->url );

						$tagname = $package->version;

						$url = 'https://' . $components['host'] . '/' . $components['organisation'] . '/' . $components['repository'] . '/releases/tag/' . $tagname;

						$content .= "\n";
						$content .= "\t" . 'Release notes: ' . $url;
					}
				}

				$lines[ $key ] = $content;
			}
		}

		if ( 0 === \count( $lines ) ) {
			return '';
		}

		$content = "\n";

		$content .= '### Composer' . "\n";
		$content .= "\n";
		$content .= \implode( "\n", $lines );

		return $content;
	}

	/**
	 * Run Composer script.
	 * 
	 * @param string          $script Script to run.
	 * @param string          $cwd    Directory.
	 * @param OutputInterface $output Output interface.
	 * @return void
	 */
	private function run_composer_script( $script, $cwd, $output ) {
		$composer_json_file = $cwd . '/composer.json';

		if ( ! \is_readable( $composer_json_file ) ) {
			return;
		}

		$json = file_get_contents( $composer_json_file );

		$data = json_decode( $json );

		if ( ! \is_object( $data ) ) {
			return;
		}

		if ( ! \property_exists( $data, 'scripts' ) ) {
			return;
		}

		if ( ! \property_exists( $data->scripts, $script ) ) {
			return;
		}

		$process_helper = $this->getHelper( 'process' );

		$process = $this->new_process( 'composer run-script ' . $script, $cwd );

		$process_helper->mustRun( $output, $process );
	}
}
