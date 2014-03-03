<?php

global $wp_filesystem;

if( !defined( 'ABSPATH' ) ) die();

include_once ABSPATH . 'wp-admin/includes/file.php';

/**
 * Class SearchWPIndexer is responsible for generating the search index
 */
class SearchWPIndexer
{
	/**
	 * @var object Stores post object during indexing
	 * @since 1.0
	 */
	private $post;

	/**
	 * @var bool Whether there are posts left to index
	 * @since 1.0
	 */
	private $unindexedPosts = false;

	/**
	 * @var int The maximum weight for a single term
	 * @since 1.0
	 */
	private $weightLimit = 500;

	/**
	 * @var bool Whether the indexer should index numbers
	 * @since 1.0
	 */
	private $indexNumbers = false;

	/**
	 * @var int Internal counter
	 * @since 1.0
	 */
	private $count = 0;

	/**
	 * @var array Common words
	 * @since 1.0
	 */
	private $common = array();

	/**
	 * @var int Maximum number of times we should try to index a post
	 */
	private $maxAttemptsToIndex = 2;

	/**
	 * @var bool Whether to index Attachments at all
	 */
	private $indexAttachments = true;

	/**
	 * @var array Character entities as specified by Ando Saabas in Sphider http://www.sphider.eu/
	 * @since 1.0
	 */
	private $entities = array(
		"&amp" => "&", "&apos" => "'", "&THORN;" => "Þ", "&szlig;" => "ß", "&agrave;" => "à", "&aacute;" => "á",
		"&acirc;" => "â", "&atilde;" => "ã", "&auml;" => "ä", "&aring;" => "å", "&aelig;" => "æ", "&ccedil;" => "ç",
		"&egrave;" => "è", "&eacute;" => "é", "&ecirc;" => "ê", "&euml;" => "ë", "&igrave;" => "ì", "&iacute;" => "í",
		"&icirc;" => "î", "&iuml;" => "ï", "&eth;" => "ð", "&ntilde;" => "ñ", "&ograve;" => "ò", "&oacute;" => "ó",
		"&ocirc;" => "ô", "&otilde;" => "õ", "&ouml;" => "ö", "&oslash;" => "ø", "&ugrave;" => "ù", "&uacute;" => "ú",
		"&ucirc;" => "û", "&uuml;" => "ü", "&yacute;" => "ý", "&thorn;" => "þ", "&yuml;" => "ÿ",
		"&Agrave;" => "à", "&Aacute;" => "á", "&Acirc;" => "â", "&Atilde;" => "ã", "&Auml;" => "ä",
		"&Aring;" => "å", "&Aelig;" => "æ", "&Ccedil;" => "ç", "&Egrave;" => "è", "&Eacute;" => "é", "&Ecirc;" => "ê",
		"&Euml;" => "ë", "&Igrave;" => "ì", "&Iacute;" => "í", "&Icirc;" => "î", "&Iuml;" => "ï", "&ETH;" => "ð",
		"&Ntilde;" => "ñ", "&Ograve;" => "ò", "&Oacute;" => "ó", "&Ocirc;" => "ô", "&Otilde;" => "õ", "&Ouml;" => "ö",
		"&Oslash;" => "ø", "&Ugrave;" => "ù", "&Uacute;" => "ú", "&Ucirc;" => "û", "&Uuml;" => "ü", "&Yacute;" => "ý",
		"&Yhorn;" => "þ", "&Yuml;" => "ÿ"
	);

	/**
	 * @var array Post IDs to forcibly exclude from indexing process
	 */
	private $excludeFromIndex = array();


	/**
	 * @var array|string post type(s) to include when indexing
	 */
	private $postTypesToIndex = 'any';


	/**
	 * @var string|array post status(es) to include when indexing
	 *
	 * @since 1.6.10
	 */
	private $post_statuses = 'publish';


	/**
	 * @var int The maximum length of a term, as defined by the database schema
	 *
	 * @since 1.8.4
	 */
	private $max_term_length = 80;


	/**
	 * Constructor
	 *
	 * @param string $hash The key used to validate instantiation
	 * @since 1.0
	 */
	public function __construct( $hash = '' ) {
		// make sure we've got a valid request to index
		if ( get_transient( 'searchwp' ) !== $hash ) {
			do_action( 'searchwp_log', 'Invalid index request ' . $hash );
		} else {
			// allow dev to completely disable indexing of Attachments to save indexing time
			$this->indexAttachments = apply_filters( 'searchwp_index_attachments', $this->indexAttachments );

			// allow dev to customize post statuses are included
			$this->post_statuses = (array) apply_filters( 'searchwp_post_statuses', $this->post_statuses, null );
			foreach( $this->post_statuses as $post_status_key => $post_status_value ) {
				$this->post_statuses[$post_status_key] = sanitize_key( $post_status_value );
			}

			if ( ! is_bool( $this->indexAttachments ) ) {
				$this->indexAttachments = false;
			}

			// allow dev to forcefully omit posts from being indexed
			$this->excludeFromIndex = apply_filters( 'searchwp_prevent_indexing', array() );
			if ( ! is_array( $this->excludeFromIndex ) ) {
				$this->excludeFromIndex = array();
			}

			// allow dev to forcefully omit post types that would normally be indexed
			$this->postTypesToIndex = apply_filters( 'searchwp_indexed_post_types', $this->postTypesToIndex );

			// attachments cannot be included here, to omit attachments use the searchwp_index_attachments filter
			// so we have to check to make sure attachments were not included
			if( is_array( $this->postTypesToIndex ) ) {
				foreach( $this->postTypesToIndex as $key => $postType ) {
					if( strtolower( $postType ) == 'attachment' ) {
						unset( $this->postTypesToIndex[ $key ] );
					}
				}
			} elseif ( strtolower( $this->postTypesToIndex ) == 'attachment' ) {
				$this->postTypesToIndex = 'any';
			}

			// see if the indexer has stalled
			$this->checkIfStalled();

			// init
			$searchwp = SearchWP::instance();
			$this->common = $searchwp->common;

			// check to see if indexer is already running
			$running = get_option( SEARCHWP_PREFIX . 'running' );
			if( empty( $running ) ) {
				do_action( 'searchwp_log', 'Indexer NOW RUNNING' );
				update_option( SEARCHWP_PREFIX . 'last_activity', current_time( 'timestamp' ) );
				update_option( SEARCHWP_PREFIX . 'running', true );

				$this->updateRunningCounts();

				if ( $this->findUnindexedPosts() !== false ) {
					// index this chunk of posts
					$this->index();

					// clean up
					do_action( 'searchwp_log', 'Indexing chunk complete' );
					update_option( SEARCHWP_PREFIX . 'running', false );

					// reset the transient
					delete_transient( 'searchwp' );
					$hash = sha1( uniqid( 'searchwpindex' ) );
					set_transient( 'searchwp', $hash );

					do_action( 'searchwp_log', 'Request index (internal loopback) ' . trailingslashit( site_url() ) . '?swpnonce=' . $hash );

					// auto-throttle based on load
					$waitTime = 1;

					if( function_exists( 'sys_getloadavg' ) ) {
						$load = sys_getloadavg();
						$loadThreshold = abs( apply_filters( 'searchwp_load_maximum', 1 ) );

						// if the load has breached the threshold, scale the wait time
						if( $load[0] > $loadThreshold ) {
							$waitTime = 5 * ceil( $load[0] );
							do_action( 'searchwp_log', 'Load threshold (' . $loadThreshold . ') has been breached! Current load: ' . $load[0] . '. Automatically injecting a wait time of ' . $waitTime );
						}
					}

					// allow developers to throttle the indexer
					$waitTime = absint( apply_filters( 'searchwp_indexer_throttle', $waitTime ) );
					$iniMaxExecutionTime = absint( ini_get( 'max_execution_time' ) ) - 5;
					if( $iniMaxExecutionTime < 10 ) $iniMaxExecutionTime = 10;
					if( $waitTime > $iniMaxExecutionTime ) {
						do_action( 'searchwp_log', 'Requested throttle of ' . $waitTime . 's exceeds max execution time, forcing ' . $iniMaxExecutionTime . 's' );
						$waitTime = $iniMaxExecutionTime;
					}

					$memoryUse = size_format( memory_get_usage() );
					do_action( 'searchwp_log', 'Memory usage before throttle: ' . $memoryUse );

					do_action( 'searchwp_log', 'Sleeping for ' . $waitTime . 's' );
					sleep( $waitTime );
					do_action( 'searchwp_log', 'Done sleeping' );

					$memoryUse = size_format( memory_get_usage() );
					do_action( 'searchwp_log', 'Memory usage after throttle: ' . $memoryUse );

					$timeout = abs( apply_filters( 'searchwp_timeout', 0.02 ) );

					// recursive trigger
					wp_remote_post(
						trailingslashit( site_url() ),
						array(
							'body'        => array( 'swpnonce' => $hash ),
							'blocking'    => false,
							'user-agent'  => 'SearchWP',
							'timeout'     => $timeout,
							'sslverify'   => false
						)
					);
				} else {
					do_action( 'searchwp_log', 'Nothing left to index' );
					$initial = get_option( SEARCHWP_PREFIX . 'initial' );
					if ( empty( $initial ) ) {
						wp_clear_scheduled_hook( 'swp_indexer' ); // clear out the pre-initial-index cron event
						do_action( 'searchwp_log', 'Initial index complete' );
						update_option( SEARCHWP_PREFIX . 'initial', true );
					}
				}

				// done indexing
				update_option( SEARCHWP_PREFIX . 'running', false );
				delete_option( SEARCHWP_PREFIX . 'last_activity' );
			}
		}
	}


	/**
	 * Determine the number of posts left to index, total post count, and how many posts have been indexed already
	 *
	 * @since 1.0
	 */
	function updateRunningCounts() {
		$total = intval( $this->countTotalPosts() );
		$indexed = intval( $this->indexedCount() );

		// edge case: if an index was performed and attachments indexed, then the user decides to disable
		// the indexing of attachments, the indexed count could potentially be greater than the total
		if ( $indexed > $total ) {
			$indexed = $total;
		}

		$remaining = intval( $total - $indexed );

		update_option( SEARCHWP_PREFIX . 'total', $total );
		update_option( SEARCHWP_PREFIX . 'remaining', $remaining );
		update_option( SEARCHWP_PREFIX . 'done', $indexed );

		do_action( 'searchwp_log', 'Updating counts: ' . $total . ' ' . $remaining . ' ' . $indexed );

		if ( $remaining < 1 ) {
			do_action( 'searchwp_log', 'Setting initial' );
			update_option( SEARCHWP_PREFIX . 'initial', true );
		}
	}


	/**
	 * Checks to see if the indexer has stalled with posts left to index
	 *
	 * @since 1.0
	 */
	function checkIfStalled() {
		do_action( 'searchwp_log', 'checkIfStalled()' );
		// if the last activity was over three minutes ago, let's reset and notify of an issue
		//		(it shouldn't take 3 minutes to index 10 posts)
		if( false !== get_option( SEARCHWP_PREFIX . 'last_activity' ) ) {
			if( current_time( 'timestamp' ) > get_option( SEARCHWP_PREFIX . 'last_activity' ) + 180 ) {
				// stalled
				do_action( 'searchwp_log', 'Stalled' );
				update_option( SEARCHWP_PREFIX . 'running', false );
				delete_transient( 'searchwp' );
				$hash = sha1( uniqid( 'searchwpindex' ) );
				set_transient( 'searchwp', $hash );
				do_action( 'searchwp_log', 'Request index (from stalled) ' . trailingslashit( site_url() ) . '?swpnonce=' . $hash );

				$timeout = abs( apply_filters( 'searchwp_timeout', 0.02 ) );
				wp_remote_post(
					trailingslashit( site_url() ),
					array(
						'body'        => array( 'swpnonce' => $hash ),
						'blocking'    => false,
						'user-agent'  => 'SearchWP',
						'timeout'     => $timeout,
						'sslverify'   => false
					)
				);
			}
		} else {
			update_option( SEARCHWP_PREFIX . 'last_activity', current_time( 'timestamp' ) );
		}
	}


	/**
	 * Sets post property
	 *
	 * @param $post object WordPress Post object
	 * @since 1.0
	 */
	function setPost( $post ) {
		$this->post = $post;

		// append Custom Field data
		$this->post->custom = get_post_custom( $post->ID );
	}


	/**
	 * Count the total number of posts in this WordPress installation
	 *
	 * @return int Total number of posts
	 * @since 1.0
	 */
	function countTotalPosts() {
		$args = array(
			'posts_per_page'    => 1,
			'post_type'         => $this->postTypesToIndex,
			'post_status'       => $this->post_statuses,
			'post__not_in'      => $this->excludeFromIndex,
			'suppress_filters'  => true,
			'meta_query'        => array(
				array(
					'key'           => '_' . SEARCHWP_PREFIX . 'skip',
					'value'         => '',	// only want media that hasn't failed indexing multiple times
					'compare'       => 'NOT EXISTS',
					'type'          => 'BINARY'
				)
			)
		);

		$totalPosts = new WP_Query( $args );

		$totalMedia = 0;  // in case Attachment indexing is disabled
		if ( $this->indexAttachments ) {
			// also check for media
			$args = array(
				'posts_per_page'    => 1,
				'post_type'         => 'attachment',
				'post_status'       => 'inherit',
				'post__not_in'      => $this->excludeFromIndex,
				'suppress_filters'  => true,
				'meta_query'        => array(
					array(
						'key'           => '_' . SEARCHWP_PREFIX . 'skip',
						'value'         => '',	// only want media that hasn't failed indexing multiple times
						'compare'       => 'NOT EXISTS',
						'type'          => 'BINARY'
					)
				)
			);

			$totalMediaRef = new WP_Query( $args );
			$totalMedia = absint( $totalMediaRef->found_posts );
		}

		return absint( $totalPosts->found_posts ) + $totalMedia;
	}


	/**
	 * Count the number of posts that have been indexed
	 *
	 * @return int Number of posts that have been indexed
	 * @since 1.0
	 */
	function indexedCount() {
		$postTypesToCount = $this->postTypesToIndex;
		if( is_array( $postTypesToCount ) ) {
			$postTypesToCount[] = 'attachment';
		}

		$args = array(
			'posts_per_page'    => 1,
			'post_type'         => $postTypesToCount,
			'post_status'       => $this->post_statuses,
			'suppress_filters'  => true,
			'meta_query'        => array(
				'relation'          => 'AND',
				array(
					'key'           => '_' . SEARCHWP_PREFIX . 'indexed',
					'compare'       => 'EXISTS',
					'type'          => 'BINARY'
				),
				array(
					'key'           => '_' . SEARCHWP_PREFIX . 'skip',
					'value'         => '',	// only want media that hasn't failed indexing multiple times
					'compare'       => 'NOT EXISTS',
					'type'          => 'BINARY'
				)
			),
			// TODO: should we include 'exclude_from_search' for accuracy?
		);

		if( $this->indexAttachments ) {
			$args['post_status'] = 'any';
		}

		$indexed = new WP_Query( $args );

		return absint( $indexed->found_posts );
	}


	/**
	 * Query for posts that have not been indexed yet
	 *
	 * @return array|bool Posts (max 10) that have yet to be indexed
	 * @since 1.0
	 */
	function findUnindexedPosts() {
		// everything that's been indexed has a postmeta flag
		// so we'll use that to determine what's left

		// we're going to index everything regardless of 'exclude_from_search' because
		// no event fires if that changes over time, so we're going to offload that
		// to be taken into consideration at query time

		$indexChunk = apply_filters( 'searchwp_index_chunk_size', 8 );

		$args = array(
			'posts_per_page'  => intval( $indexChunk ),
			'post_type'       => $this->postTypesToIndex,
			'post_status'     => $this->post_statuses,
			'post__not_in'    => $this->excludeFromIndex,
			'meta_query'      => array(
				'relation'      => 'AND',
				array(
					'key'         => '_' . SEARCHWP_PREFIX . 'indexed',
					'value'       => '',	// http://core.trac.wordpress.org/ticket/23268
					'compare'     => 'NOT EXISTS',
					'type'        => 'BINARY'
				),
				array(
					'key'         => '_' . SEARCHWP_PREFIX . 'skip',
					'value'       => '',	// only want media that hasn't failed indexing multiple times
					'compare'     => 'NOT EXISTS',
					'type'        => 'BINARY'
				),
				array( // if a PDF was flagged during indexing, we don't want to keep trying
					'key'         => '_' . SEARCHWP_PREFIX . 'review',
					'value'       => '',
					'compare'     => 'NOT EXISTS',
					'type'        => 'BINARY'
				)
			)
		);

		$unindexedPosts = get_posts( $args );

		// also check for media
		if ( $this->indexAttachments !== false ) {
			$indexChunk = apply_filters( 'searchwp_index_chunk_size', 5 );
			$mediaArgs = array(
				'posts_per_page'	=> intval( $indexChunk ),
				'post_type' 			=> 'attachment',
				'post_status'			=> 'inherit',
				'post__not_in'    => $this->excludeFromIndex,
				'meta_query' 			=> array(
					'relation'			=> 'AND',
					array(
						'key' 				=> '_' . SEARCHWP_PREFIX . 'indexed',
						'value' 			=> '',	// http://core.trac.wordpress.org/ticket/23268
						'compare' 		=> 'NOT EXISTS',
						'type'				=> 'BINARY'
					),
					array(
						'key' 				=> '_' . SEARCHWP_PREFIX . 'skip',
						'value' 			=> '',	// only want media that hasn't failed indexing multiple times
						'compare' 		=> 'NOT EXISTS',
						'type'				=> 'BINARY'
					)
				)
			);

			$unindexedMedia = get_posts( $mediaArgs );

			$this->unindexedPosts = !empty( $unindexedPosts ) || !empty( $unindexedMedia ) ? array_merge( $unindexedPosts, $unindexedMedia ) : false;
		} else {
			$this->unindexedPosts = !empty( $unindexedPosts ) ? $unindexedPosts : false;
		}

		return $this->unindexedPosts;
	}


	/**
	 * Index posts stored in $this->unindexedPosts
	 *
	 * @since 1.0
	 */
	function index() {
		global $wp_filesystem, $wpdb;

		if ( is_array( $this->unindexedPosts ) && count( $this->unindexedPosts ) ) {
			while ( ( $unindexedPost = current( $this->unindexedPosts ) ) !== false ) {
				$this->setPost( $unindexedPost );

				// log the attempt
				$count = get_post_meta( $this->post->ID, '_' . SEARCHWP_PREFIX . 'attempts', true );
				if ( $count == false ) {
					$count = 0;
				} else {
					$count = intval( $count );
				}

				$count++;

				// increment our counter to prevent the indexer getting stuck on a gigantic PDF
				update_post_meta( $this->post->ID, '_' . SEARCHWP_PREFIX . 'attempts', $count );
				do_action( 'searchwp_log', 'Attempt ' . $count . ' at indexing ' . $this->post->ID );

				// if we breached the maximum number of attempts, flag it to skip
				$this->maxAttemptsToIndex = absint( apply_filters( 'searchwp_max_index_attempts', $this->maxAttemptsToIndex ) );
				if ( intval( $count ) > $this->maxAttemptsToIndex ) {
					do_action( 'searchwp_log', 'Too many indexing attempts on ' . $this->post->ID . ' (' . $this->maxAttemptsToIndex . ') - skipping' );
					// flag it to be skipped
					update_post_meta( $this->post->ID, '_' . SEARCHWP_PREFIX . 'skip', true );
				} else {
					// check to see if we're running a second pass on terms
					$termCache = get_post_meta( $this->post->ID, '_' . SEARCHWP_PREFIX . 'terms', true );
					if ( ! is_array( $termCache ) ) {
						// if it's an attachment, we want the permalink
						$slug = $this->post->post_type == 'attachment' ? str_replace( get_bloginfo( 'wpurl' ), '', get_permalink( $this->post->ID ) ) : '';

						// we allow users to override the extracted content from documents, if they have done so this flag is set
						$skipDocProcessing = get_post_meta( $this->post->ID, '_' . SEARCHWP_PREFIX . 'skip_doc_processing', true );
						$omitDocProcessing = apply_filters( 'searchwp_omit_document_processing', false );

						if ( ! $skipDocProcessing && ! $omitDocProcessing ) {
							// if it's a PDF we need to populate our Custom Field with it's content
							if ( $this->post->post_mime_type == 'application/pdf' ) {
								// grab the filename of the PDF
								$filename = get_attached_file( $this->post->ID );

								// allow for external PDF content extraction
								$pdfContent = apply_filters( 'searchwp_external_pdf_processing', '', $filename, $this->post->ID );

								// only try to extract content if we have control over the max execution time
								// OR the external processing has already provided the PDF content we're looking for
								@ini_set( 'max_execution_time', 60 );
								if ( 60 == absint( ini_get( 'max_execution_time' ) ) || ! empty( $pdfContent ) ) {
									if ( empty( $pdfContent ) ) {
										$pdfParser = new PDF2Text();
										$pdfParser->setFilename( $filename );
										$pdfParser->decodePDF();
										$pdfContent = $pdfParser->output();
										$pdfContent = preg_replace( '/[\x00-\x1F\x80-\xFF]/', ' ', $pdfContent );
										$pdfContent = trim( str_replace( "\n", " ", $pdfContent ) );
									}

									// check to see if the first pass produced nothing or concatenated strings
									$fullContentLength = strlen( $pdfContent );
									$numberOfSpaces = substr_count($pdfContent, ' ');;
									if ( empty( $pdfContent ) || ( ( $numberOfSpaces / $fullContentLength ) * 100 < 10 ) ) {
										WP_Filesystem();
										$filecontent = $wp_filesystem->exists( $filename ) ? $wp_filesystem->get_contents( $filename ) : '';

										if ( false != strpos( $filecontent, 'trailer' ) ) {
											$pdfContent = '';
											$pdf = new pdf( get_attached_file( $this->post->ID ) );
											$pages = $pdf->get_pages();
											if ( ! empty( $pages ) ) {
												while ( list( $nr, $page ) = each( $pages ) ) {
													$pdfContent .= $page->get_text();
												}
											}
										} else {
											// empty out the content so wacky concatenations are not indexed
											$pdfContent = '';

											// flag it for further review
											update_post_meta( $this->post->ID, '_' . SEARCHWP_PREFIX . 'review', 1 );
											update_post_meta( $this->post->ID, '_' . SEARCHWP_PREFIX . 'skip', 1 );
										}
									}

									$pdfContent = trim( $pdfContent );

									if ( ! empty( $pdfContent ) ) {
										delete_post_meta( $this->post->ID, SEARCHWP_PREFIX . 'content' );
										update_post_meta( $this->post->ID, SEARCHWP_PREFIX . 'content', $pdfContent );
									}
								}
							} elseif ( $this->post->post_mime_type == 'text/plain' ) {
								// if it's plain text, index it's content
								WP_Filesystem();
								$filename = get_attached_file( $this->post->ID );
								$textContent = $wp_filesystem->exists( $filename ) ? $wp_filesystem->get_contents( $filename ) : '';
								$textContent = preg_replace( '/[\x00-\x1F\x80-\xFF]/', ' ', $textContent );
								$textContent = str_replace( "\n", " ", $textContent );
								if ( ! empty( $textContent ) ) {
									update_post_meta( $this->post->ID, SEARCHWP_PREFIX . 'content', $textContent );
								}
							} else {
								// all other file types
							}
						}

						$postTerms              = array();
						$postTerms['title']     = $this->indexTitle();
						$postTerms['slug']      = $this->indexSlug( str_replace( '/', ' ', $slug ) );
						$postTerms['content']   = $this->indexContent();
						$postTerms['excerpt']   = $this->indexExcerpt();
						$postTerms['comments']  = $this->indexComments();

						// index taxonomies
						$taxonomies = get_object_taxonomies( $this->post->post_type );
						if ( ! empty( $taxonomies ) ) {
							while ( ( $taxonomy = current( $taxonomies ) ) !== false ) {
								$terms = get_the_terms( $this->post->ID, $taxonomy );
								if ( ! empty( $terms ) ) {
									$postTerms['taxonomy'][$taxonomy] = $this->indexTaxonomyTerms( $taxonomy, $terms );
								}
								next( $taxonomies );
							}
							reset( $taxonomies );
						}

						// index custom fields
						$customFields = get_post_custom( $this->post->ID );
						if ( ! empty( $customFields ) ) {
							while ( ( $customFieldValue = current( $customFields ) ) !== false ) {
								$customFieldName = key( $customFields );

								$excludedCustomFieldKeys = apply_filters( 'searchwp_excluded_custom_fields', array(
									'_edit_lock',
									'_wp_page_template',
									'_edit_last',
									'_wp_old_slug',
								) );

								$omitWpMetadata = apply_filters( 'searchwp_omit_wp_metadata', true );
								if( !$omitWpMetadata || ( $omitWpMetadata && !in_array( $customFieldName, $excludedCustomFieldKeys ) ) ) {
									// allow devs to swap out their own content
									// e.g. parsing ACF Relationship fields (that store only post IDs) to actually retrieve that content at runtime
									$customFieldValue = apply_filters( 'searchwp_custom_fields', $customFieldValue, $customFieldName, $this->post );
									$customFieldValue = apply_filters( "searchwp_custom_field_{$customFieldName}", $customFieldValue, $this->post );
									$postTerms['customfield'][$customFieldName] = $this->indexCustomField( $customFieldName, $customFieldValue );
								}
								next( $customFields );
							}
							reset( $customFields );
						}

						// allow developer to store arbitrary information a la Custom Fields (without them actually Custom Fields)
						$extraMetadata = apply_filters( "searchwp_extra_metadata", false, $this->post );
						if( $extraMetadata ) {
							if( is_array( $extraMetadata ) ) {
								foreach( $extraMetadata as $extraMetadataKey => $extraMetadataValue ) {
									// TODO: make sure there are no collisions?
//									while( isset( $postTerms['customfield'][$extraMetadataKey] ) ) {
//										$extraMetadataKey .= '_';
//									}
									$postTerms['customfield'][$extraMetadataKey] = $this->indexCustomField( $extraMetadataKey, $extraMetadataValue );
								}
							}
						}

						// TODO: index author info?

						// we need to break out the terms from all of this content
						$termCountBreakout = array();

						if( is_array( $postTerms ) && count( $postTerms ) ) {
							foreach( $postTerms as $type => $terms ) {
								switch( $type ) {
									case 'title':
									case 'slug':
									case 'content':
									case 'excerpt':
									case 'comments':
										if( is_array( $terms ) && count( $terms ) ) {
											foreach( $terms as $term ) {
												$termCountBreakout[$term['term']][$type] = $term['count'];
											}
										}
										break;

									case 'taxonomy':
									case 'customfield':
										if( is_array( $terms ) && count( $terms ) ) {
											foreach( $terms as $name => $nameTerms ) {
												if( is_array( $nameTerms ) && count( $nameTerms ) ) {
													foreach( $nameTerms as $nameTerm ) {
														$termCountBreakout[$nameTerm['term']][$type][$name] = $nameTerm['count'];
													}
												}
											}
										}
										break;

								}
							}
						}
					} else {
						$termCountBreakout = $termCache;

						// if there was a term cache, this repeated processing doesn't count, so decrement it
						delete_post_meta( $this->post->ID, '_' . SEARCHWP_PREFIX . 'attempts' );
						delete_post_meta( $this->post->ID, '_' . SEARCHWP_PREFIX . 'skip' );
					}

					// unless the term chunk limit says otherwise, we're going to flag this as being OK to log as indexed
					$flagAsIndexed = true;

					// we now have a multidimensional array of terms with counts per type in $termCountBreakout
					// if the term count is huge, we need to split up this process so as to avoid
					// hitting upper PHP execution time limits (term insertion is heavy), so we'll chunk the array of terms

					$termChunkMax = 500;

					// try to set a better default based on php.ini's memory_limit
					$memoryLimit = ini_get( 'memory_limit' );
					if ( preg_match( '/^(\d+)(.)$/', $memoryLimit, $matches ) ) {
						if ( $matches[2] == 'M' ) {
							$termChunkMax = ( (int) $matches[1] ) * 15;  // 15 terms per MB RAM
						} else {
							// memory was set in K...
							$termChunkMax = 100;
						}
					}

					do_action( 'searchwp_log', '$termChunkMax = ' . $termChunkMax );
					$termChunkLimit = apply_filters( 'searchwp_process_term_limit', $termChunkMax );
					do_action( 'searchwp_log', '$termChunkLimit = ' . $termChunkLimit );

					if ( count( $termCountBreakout ) > $termChunkLimit ) {
						$acceptableTermCountBreakout = array_slice( $termCountBreakout, 0, $termChunkLimit );

						// if we haven't pulled all of the terms, we can't consider this post indexed...
						if ( $termChunkLimit < count( $termCountBreakout ) - 1 ) {
							$flagAsIndexed = false;

							// save the term breakout so we don't have to do it again
							$remainingTerms = array_slice( $termCountBreakout, $termChunkLimit + 1 );
							update_post_meta( $this->post->ID, '_' . SEARCHWP_PREFIX . 'terms', $remainingTerms );
						}

						// set the acceptable breakout as the main breakout
						$termCountBreakout = $acceptableTermCountBreakout;
					}

					$this->recordPostTerms( $termCountBreakout );
					unset( $termCountBreakout );

					// flag the post as indexed
					if( $flagAsIndexed ) {
						// clean up our stored term array if necessary
						if( $termCache ) {
							delete_post_meta( $this->post->ID, '_' . SEARCHWP_PREFIX . 'terms' );
						}

						// clean up the attempt counter
						delete_post_meta( $this->post->ID, '_' . SEARCHWP_PREFIX . 'attempts' );
						delete_post_meta( $this->post->ID, '_' . SEARCHWP_PREFIX . 'skip' );

						update_post_meta( $this->post->ID, '_' . SEARCHWP_PREFIX . 'indexed', true );
						update_post_meta( $this->post->ID, '_' . SEARCHWP_PREFIX . 'last_index', current_time( 'timestamp' ) );
					}
				}
				next( $this->unindexedPosts );
			}
			reset( $this->unindexedPosts );
		}
	}


	/**
	 * Insert an array of terms into the terms table and retrieve all term IDs from submitted terms
	 * @param array $termsArray
	 *
	 * @return array
	 * @since 1.0
	 */
	function preProcessTerms( $termsArray = array() ) {
		global $wpdb;

		if ( ! is_array( $termsArray ) || empty( $termsArray ) ) {
			return array();
		}

		// get our database vars prepped
		$termsTable = $wpdb->prefix . SEARCHWP_DBPREFIX . 'terms';

		$stemmer = new SearchWPStemmer();

		$terms = $newTerms = $newTermsSQL = array();

		while ( ( $counts = current( $termsArray ) ) !== false ) {
			$termToAdd = (string) key( $termsArray );

			// generate the reverse (UTF-8)
			preg_match_all( '/./us', $termToAdd, $contentr );
			$revTerm = join( '', array_reverse( $contentr[0] ) );

			// find the stem
			$unstemmed = $termToAdd;
			$maybeStemmed = apply_filters( 'searchwp_custom_stemmer', $unstemmed );

			// if the term was stemmed via the filter use it, else generate our own
			$stem = ( $unstemmed == $maybeStemmed ) ? $stemmer->stem( $termToAdd ) : $maybeStemmed;

			// store the record
			$terms[] = $wpdb->prepare( '%s', $termToAdd );
			$newTermsSQL[] = "(%s,%s,%s)";
			$newTerms = array_merge( $newTerms, array( $termToAdd, $revTerm, $stem ) );
			next( $termsArray );
		}
		reset( $termsArray );

		// insert all of the terms into the terms table so each gets an ID
		$attemptCount = 1;
		$maxAttempts = absint( apply_filters( 'searchwp_indexer_max_attempts', 4 ) ) + 1;  // try to recover 5 times
		$insert_result = $wpdb->query(
		                      $wpdb->prepare( "INSERT IGNORE INTO {$termsTable} (term,reverse,stem) VALUES " . implode( ',', $newTermsSQL ), $newTerms )
		);
		while( ( is_wp_error( $insert_result ) || false === $insert_result ) && $attemptCount < $maxAttempts ) {
			// sometimes a deadlock can happen, wait a second then try again
			do_action( 'searchwp_log', 'INSERT Deadlock ' . $attemptCount . '/' . $maxAttempts );
			sleep(3);
			$attemptCount++;
		}

		if( $attemptCount > 1 ) {
			do_action( 'searchwp_log', 'Recovered from Deadlock at ' . $attemptCount . '/' . $maxAttempts );
		}

		// retrieve IDs for all terms
		$termIDs = $wpdb->get_results( "SELECT id, term FROM {$termsTable} WHERE term IN( " . implode( ',', $terms ) . " )", 'OBJECT_K');

		// match term IDs to original terms with counts
		if( is_array( $termIDs ) ) {
			while ( ( $termIDMeta = current( $termIDs ) ) !== false ) {
				$termID = key( $termIDs );

				// append the term ID to the original $termsArray

				while ( ( $counts = current( $termsArray ) ) !== false ) {
					$termsArrayTerm = key( $termsArray );
					if( $termsArrayTerm == $termIDMeta->term ) {
						$termsArray[$termsArrayTerm]['id'] = $termIDMeta->id;
						break;
					}
					next( $termsArray );
				}
				reset( $termsArray );
				next( $termIDs );
			}
			reset( $termIDs );
		}

		return $termsArray;
	}


	/**
	 * Insert terms with counts into the database
	 *
	 * @param array $termsArray The terms to insert
	 * @return bool Whether the insert was successful
	 * @since 1.0
	 */
	function recordPostTerms( $termsArray = array() ) {
		global $wpdb;

		if ( ! is_array( $termsArray ) || empty( $termsArray ) ) {
			return false;
		}

		$success = true;	// track whether or not the database insert went okay

		// get our database vars prepped
		$termsTable = $wpdb->prefix . SEARCHWP_DBPREFIX . 'terms';

		// retrieve IDs for all terms
		$termsArray = $this->preProcessTerms( $termsArray );

		// storage in prep for bulk INSERTs
		$indexTerms       = $indexTermsSQL        = array();
		$customFieldTerms = $customFieldTermsSQL  = array();
		$taxonomyTerms    = $taxonomyTermsSQL     = array();

		// insert terms into index
		while ( ( $term = current( $termsArray ) ) !== false ) {
			$key = key( $termsArray );

			if ( ! empty( $term ) ) {

				// if an ID is somehow missing, grab it
				// TODO: determine if this is still (ever) an issue
				if ( ! isset( $term[ 'id' ] ) ) {
					$term['id'] = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . $termsTable . " WHERE term = %s", $key ) );
				}

				$termID = isset( $term['id'] ) ? absint( $term['id'] ) : 0;

				// insert the counts for our standard fields
				$indexTermsSQL[] = "(%d,%d,%d,%d,%d,%d,%d)";
				$indexTerms = array_merge( $indexTerms, array(
					$termID,
					isset( $term['content'] )  ? absint( $term['content'] ) : 0,
					isset( $term['title'] )    ? absint( $term['title'] ) : 0,
					isset( $term['comments'] ) ? absint( $term['comments'] ) : 0,
					isset( $term['excerpt'] )  ? absint( $term['excerpt'] ) : 0,
					isset( $term['slug'] )     ? absint( $term['slug'] ) : 0,
					absint( $this->post->ID )
				) );

				// insert our custom field counts
				if( isset( $term['customfield'] ) && is_array( $term['customfield'] ) && count( $term['customfield'] ) ) {

					while ( ( $customFieldCount = current( $term['customfield'] ) ) !== false ) {
						$customField = key( $term['customfield'] );
						$customFieldTermsSQL[] = "(%s,%d,%d,%d)";
						$customFieldTerms = array_merge( $customFieldTerms, array(
							$customField,
							isset( $term['id'] ) ? absint( $term['id'] ) : 0,
							absint( $customFieldCount ),
							absint( $this->post->ID )
						) );
						next( $term['customfield'] );
					}
					reset( $term['customfield'] );
				}

				// index our taxonomy counts
				if( isset( $term['taxonomy'] ) && is_array( $term['taxonomy'] ) && count( $term['taxonomy'] ) ) {
					while ( ( $taxonomyCount = current( $term['taxonomy'] ) ) !== false ) {
						$taxonomyName = key( $term['taxonomy'] );
						$taxonomyTermsSQL[] = "(%s,%d,%d,%d)";
						$taxonomyTerms = array_merge( $taxonomyTerms, array(
							$taxonomyName,
							isset( $term['id'] ) ? absint( $term['id'] ) : 0,
							absint( $taxonomyCount ),
							absint( $this->post->ID )
						) );
						next( $term['taxonomy'] );
					}
					reset( $term['taxonomy'] );
				}

			}
			next( $termsArray );
		}
		reset( $termsArray );

		// INSERT index terms
		if( !empty( $indexTerms ) ) {
			$indexTable = $wpdb->prefix . SEARCHWP_DBPREFIX . 'index';
			$wpdb->query(
				$wpdb->prepare( "INSERT INTO {$indexTable} (term,content,title,comment,excerpt,slug,post_id) VALUES " . implode( ',', $indexTermsSQL ), $indexTerms )
			);
		}

		// INSERT custom field terms
		if( !empty( $customFieldTerms ) ) {
			$cfTable = $wpdb->prefix . SEARCHWP_DBPREFIX . 'cf';
			$wpdb->query(
				$wpdb->prepare( "INSERT INTO {$cfTable} (metakey,term,count,post_id) VALUES " . implode( ',', $customFieldTermsSQL ), $customFieldTerms )
			);
		}

		// INSERT taxonomy terms
		if( !empty( $taxonomyTerms ) ) {
			$taxTable = $wpdb->prefix . SEARCHWP_DBPREFIX . 'tax';
			$wpdb->query(
				$wpdb->prepare( "INSERT INTO {$taxTable} (taxonomy,term,count,post_id) VALUES " . implode( ',', $taxonomyTermsSQL ), $taxonomyTerms )
			);
		}

		return $success;
	}


	/**
	 * Determine keyword weights for a given string. Our 'weights' are not traditional, but instead simple counts
	 * so as to facilitate changing weights on the fly and not having to reindex. Actual weights are computed at
	 * query time.
	 *
	 * @param string $string The string from which to obtain weights
	 * @return array Terms and their correlating counts
	 * @since 1.0
	 */
	function getTermCounts( $string = '' ) {
		$wordArray = array();

		if( is_string( $string ) && !empty( $string ) ) {
			$string = strtolower( $string );
			$exploded = explode( " ", $string );

			// ensure word length obeys database schema
			foreach ( $exploded as $term_key => $term_term ) {
				$exploded[$term_key] = trim( $term_term );
				if( strlen( $term_term ) > $this->max_term_length ) {
					// just drop it, it's useless anyway
					unset( $exploded[$term_key] );
				}
			}
			$exploded = array_values( $exploded );

			$wordArray = $this->getWordCountFromArray( $exploded );
		}

		return $wordArray;
	}


	/**
	 * Remove accents from the submitted string
	 *
	 * Written by Ando Saabas in Sphider http://www.sphider.eu/
	 *
	 * @param string $string The string from which to remove accents
	 * @return string
	 * @since 1.0
	 */
	function removeAccents( $string ) {
		return( strtr( $string, "ÀÁÂÃÄÅÆàáâãäåæÒÓÔÕÕÖØòóôõöøÈÉÊËèéêëðÇçÐÌÍÎÏìíîïÙÚÛÜùúûüÑñÞßÿý",
			"aaaaaaaaaaaaaaoooooooooooooeeeeeeeeecceiiiiiiiiuuuuuuuunntsyy" ) );
	}


	/**
	 * Determine a word count for the submitted array.
	 *
	 * Modified version of Sphider's unique_array() by Ando Saabas, http://www.sphider.eu/
	 *
	 * @param array $arr
	 * @return array
	 * @since 1.0
	 */
	function getWordCountFromArray( $arr = array() ) {
		$newarr = array ();

		// set the minimum character length to count as a valid term
		$minLength = apply_filters( 'searchwp_minimum_word_length', 3 );

		while ( ( $term = current( $arr ) ) !== false ) {
			if ( ! in_array( $term, $this->common ) && ( strlen( $term ) >= absint( $minLength ) ) ) {
				$key = md5( $term );
				if ( ! isset( $newarr[$key] ) ) {
					$newarr[$key] = array(
						'term' => $term,
						'count' => 1
					);
				} else {
					$newarr[$key]['count'] = $newarr[$key]['count'] + 1;
				}
			}
			next( $arr );
		}
		reset( $arr );

		$newarr = array_values( $newarr );

		return $newarr;
	}


	/**
	 * Retrieve only the term content from the submitted string
	 *
	 * Modified from Sphider by Ando Saabas, http://www.sphider.eu/
	 *
	 * @param string $content The source content, can include markup
	 * @return string The content without markup or character encoding
	 * @since 1.0
	 */
	function cleanContent( $content = '' ) {
		if ( is_array( $content ) || is_object( $content ) ) {
			$content = $this->parseVariableForTerms( $content );
		}

		// buffer tags with spaces before removing them
		$content = preg_replace( "/<[\w ]+>/", "\\0 ", $content );
		$content = preg_replace( "/<\/[\w ]+>/", "\\0 ", $content );
		$content = strip_tags( $content );
		$content = preg_replace( "/&nbsp;/", " ", $content );

		$content = strtolower( $content );
		$content = stripslashes( $content );

		// remove punctuation
		$punctuation = array( "(", ")", "·", "'", "´", "’", "‘", "”", "“", "„", "—", "–", "×", "…", "€", "\n", ".", ",", "/", "\\", "|", "[", "]", "{", "}" );
		$content = str_replace( $punctuation, ' ', $content );
		$content = preg_replace( "/[[:punct:]]/uiU", " ", $content );
		$content = preg_replace( "/[[:space:]]/uiU", " ", $content );
		$content = trim( $content );

		return $content;
	}


	/**
	 * Get the term counts for a title
	 *
	 * @param string $title The title to index
	 * @return array|bool Terms and their associated counts
	 * @since 1.0
	 */
	function indexTitle( $title = '' ) {
		$title = ( ! is_string( $title ) || empty( $title ) ) && !empty( $this->post->post_title ) ? $this->post->post_title : $title;
		$title = $this->cleanContent( $title );

		if ( !empty( $title ) && is_string( $title ) ) {
			return $this->getTermCounts( $title );
		} else {
			return false;
		}
	}


	/**
	 * Index the filename itself
	 *
	 * @param string $filename The filename to index
	 * @return array|bool
	 */
	function indexFilename( $filename = '' ) {
		$fullFilename = explode( '.', basename( $filename ) );
		if ( isset( $fullFilename[0] ) ) {
			$filename = $fullFilename[0]; // don't care about extension
		}

		if ( ! empty( $filename ) && is_string( $filename ) ) {
			return $this->getTermCounts( $filename );
		} else {
			return false;
		}
	}


	/**
	 * Get the term counts for a filename
	 *
	 * @param string $filename The filename to index
	 * @return array|bool Terms and their associated counts
	 * @since 1.0
	 * @deprecated 1.5.1
	 */
	function extractFilenameTerms( $filename = '' ) {
		// try to retrieve keywords from filename, explode by '-' or '_'
		$fullFilename = explode( '.', basename( $filename ) );

		if ( isset( $fullFilename[0] ) ) {
			$fullFilename = $fullFilename[0]; // don't care about extension
		}

		// first explode by hyphen, then explode those pieces by underscore
		$filenamePieces = array();

		$filenameFirstPass = explode( '-', $fullFilename );
		if ( count( $filenameFirstPass ) > 1 ) {

			while ( ( $filenameSegment = current( $filenameFirstPass ) ) !== false ) {
				$filenamePieces[] = $filenameSegment;
				next( $filenameFirstPass );
			}
			reset( $filenameFirstPass );
		} else {
			$filenamePieces = array( $fullFilename );
		}

		while ( ( $filenamePiece = current( $filenamePieces ) ) !== false ) {
			$filenameSecondPass = explode( '-', $filenamePiece );
			if ( count( $filenameSecondPass ) > 1 ) {
				while ( ( $filenameSegment = current( $filenameSecondPass ) ) !== false ) {
					$filenamePieces[] = $filenameSegment;
					next( $filenameSecondPass );
				}
				reset( $filenameSecondPass );
			} else {
				$filenamePieces[] = $filenamePiece;
			}
			next( $filenamePieces );
		}
		reset( $filenamePieces );

		// if we found some pieces we'll put them back together, if not we'll use the original
		$filename = is_array( $filenamePieces ) ? implode( ' ', $filenamePieces ) : $filename;

		return $filename;
	}


	/**
	 * Get the term counts for a slug
	 *
	 * @param string $slug The slug to index
	 * @return array|bool Terms and their associated counts
	 * @since 1.0
	 */
	function indexSlug( $slug = '' ) {
		$slug = ( !is_string( $slug ) || empty( $slug ) ) && !empty( $this->post->post_name ) ? $this->post->post_name : $slug;
		$slug = str_replace( '-', ' ', $slug );
		$slug = $this->cleanContent( $slug );

		if ( ! empty( $slug ) && is_string( $slug ) ) {
			return $this->getTermCounts( $slug );
		} else {
			return false;
		}
	}


	/**
	 * Get the term counts for a content block
	 *
	 * @param string $content The content to index
	 * @return array|bool Terms and their associated counts
	 * @since 1.0
	 */
	function indexContent( $content = '' ) {
		$content = ( !is_string( $content ) || empty( $content ) ) && !empty( $this->post->post_content ) ? $this->post->post_content : $content;
		$content = $this->cleanContent( $content );

		if ( ! empty( $content ) && is_string( $content ) ) {
			return $this->getTermCounts( $content );
		} else {
			return false;
		}
	}


	/**
	 * Get the term counts for a comment
	 *
	 * @return array Terms and their associated counts
	 * @since 1.0
	 */
	function indexComments() {
		// TODO: short circuit on pingback/trackback?

		// index comments
		$comments = get_comments( array(
			'status'	=> 'approve',
			'post_id'	=> $this->post->ID
		) );

		$commentTerms = array();
		if ( ! empty( $comments ) ) {
			while ( ( $comment = current( $comments ) ) !== false ) {
				$id       = isset( $comment->comment_ID ) && ! empty( $comment->comment_ID ) ? $comment->comment_ID : null;
				$author   = isset( $comment->comment_author ) && ! empty( $comment->comment_author ) ? $comment->comment_author : null;
				$email    = isset( $comment->comment_author_email ) && ! empty( $comment->comment_author_email ) ? $comment->comment_author_email : null;

				$comment  = isset( $comment->comment_content ) && ! empty( $comment->comment_content ) ? $comment->comment_content : $comment;
				$comment  = $this->cleanContent( $comment );

				// grab all the comment data
				$author   = ! empty( $author ) && is_string( $author ) ? $author : '';
				$email    = ! empty( $email ) && is_string( $email ) ? $email : '';
				$comment  = ! empty( $comment ) && is_string( $comment ) ? $comment : '';

				$commentTerms[] = $comment;
				unset( $comment );

				if( apply_filters( 'searchwp_include_comment_author', false ) ) {
					$commentTerms[] = $author;
				}

				if( apply_filters( 'searchwp_include_comment_email', false ) ) {
					$commentTerms[] = $email;
				}
				next( $comments );
			}
			reset( $comments );
		}

		$commentTerms = $this->getTermCounts( implode( ' ', $commentTerms ) );

		return $commentTerms;
	}


	/**
	 * Index the terms within a taxonomy
	 *
	 * @param null|string $taxonomy The taxonomy name
	 * @param array $terms The terms to index
	 * @return array|bool Terms and their associated counts
	 * @since 1.0
	 */
	function indexTaxonomyTerms( $taxonomy = null, $terms = array() ) {
		// get just the term strings
		$cleanTerms = array();
		if ( is_array( $terms ) && !empty( $terms ) ) {
			while ( ( $term = current( $terms ) ) !== false ) {
				$termsKey = key( $terms );
				$cleanTerms[] = $this->cleanContent( $term->name );
				next( $terms );
			}
			reset( $terms );
		}

		$cleanTerms = trim( implode( ' ', $cleanTerms ) );

		if ( ! empty( $cleanTerms ) && is_string( $cleanTerms ) && ! empty( $taxonomy ) && is_string( $taxonomy ) ) {
			return $this->getTermCounts( $cleanTerms );
		} else {
			return false;
		}
	}


	/**
	 * Get the term counts for an excerpt
	 *
	 * @param string $excerpt The excerpt to index
	 * @return array|bool Terms and their associated counts
	 * @since 1.0
	 */
	function indexExcerpt( $excerpt = '' ) {
		$excerpt = ( !is_string( $excerpt ) || empty( $excerpt ) ) && !empty( $this->post->post_excerpt ) ? $this->post->post_excerpt : $excerpt;
		$excerpt = $this->cleanContent( $excerpt );

		if ( ! empty( $excerpt ) && is_string( $excerpt ) ) {
			return $this->getTermCounts( $excerpt );
		} else {
			return false;
		}
	}


	/**
	 * Index a Custom Field, no matter what format
	 *
	 * @param null $customFieldName Custom Field meta key
	 * @param mixed $customFieldValue Custom field value
	 * @return array|bool Terms and their associated counts
	 * @since 1.0
	 */
	function indexCustomField( $customFieldName = null, $customFieldValue ) {
		// custom fields can be pretty much anything, so we need to make sure we're unserializing, json_decoding, etc.
		$customFieldValue = $this->parseVariableForTerms( $customFieldValue );

		if ( ! empty( $customFieldName ) && is_string( $customFieldName ) && ! empty( $customFieldValue ) && is_string( $customFieldValue ) ) {
			return $this->getTermCounts( $customFieldValue );
		} else {
			return false;
		}
	}


	/**
	 * Retrieve terms from any kind of variable, even serialized and json_encode()ed values
	 *
	 * Modified from pods_sanitize() written by Scott Clark for Pods http://pods.io
	 *
	 * @param mixed $input Variable from which to obtain terms
	 * @return string Term list
	 * @since 1.0
	 */
	function parseVariableForTerms( $input ) {
		$output = '';

		// check to see if it's encoded
		if ( is_string( $input ) ) {
			if ( is_null( $json_decoded_input = json_decode( $input, true ) ) ) {
				$input = maybe_unserialize( $input );
			} else {
				$input = $json_decoded_input;
			}
		}

		// proceed with decoded input
		if( is_string( $input ) ) {
			$output = $this->cleanContent( $input );
		} elseif( is_array( $input ) || is_object( $input ) ) {
			foreach( (array) $input as $key => $val ) {
				$array_output = self::parseVariableForTerms( $val );
				if( ! is_object( $array_output ) && 'object' == gettype( $array_output ) ) {
					// we hit a __PHP_Incomplete_Class Object because a serialized object was unserialized
					$incomplete_class_output = '';
					foreach( $array_output as $array_output_key => $array_output_val ) {
						$incomplete_class_output .= ' ' . self::parseVariableForTerms( $array_output_val );
					}
					$array_output = $incomplete_class_output;
				}
				$output .= ' ' . $array_output;
			}
		} elseif( !is_bool( $input ) ) {
			// it's a number
			$output = $input;
		}

		return $output;
	}

}
