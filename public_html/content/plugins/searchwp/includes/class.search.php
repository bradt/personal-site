<?php

if( !defined( 'ABSPATH' ) ) die();

/**
 * Singleton reference
 */
global $searchwp;


/**
 * Class SearchWPSearch performs search queries on the index
 */
class SearchWPSearch
{
	/**
	 * @var string Search engine name
	 * @since 1.0
	 */
	private $engine;

	/**
	 * @var array Terms to search for
	 * @since 1.0
	 */
	private $terms;

	/**
	 * @var mixed|void Stored SearchWP settings
	 * @since 1.0
	 */
	private $settings;

	/**
	 * @var int The page of results to work with
	 * @since 1.0
	 */
	private $page;

	/**
	 * @var int The number of posts per page
	 * @since 1.0
	 */
	private $postsPer;

	/**
	 * @var string The order in which results should be returned
	 * @since 1.0
	 */
	private $order = 'DESC';

	/**
	 * @var int Total number of posts found after performing search
	 */
	public $foundPosts  = 0;

	/**
	 * @var int Total number of pages of results
	 */
	public $maxNumPages = 0;

	/**
	 * @var array Post ID storage
	 */
	public $postIDs = array();

	/**
	 * @var array Post object storage
	 */
	public $posts;

	/**
	 * @var string|array post status(es) to include when indexing
	 *
	 * @since 1.6.10
	 */
	private $post_statuses = 'publish';

	/**
	 * @var SearchWP parent
	 * @since 1.8
	 */
	private $searchwp;

	/**
	 * @var array engine settings
	 * @since 1.8
	 */
	private $engineSettings;

	/**
	 * @var SearchWPStemmer Core keyword stemmer
	 * @since 1.8
	 */
	private $stemmer;

	/**
	 * @var array Excluded post IDs
	 * @since 1.8
	 */
	private $excluded = array();

	/**
	 * @var array Included post IDs
	 * @since 1.8
	 */
	private $included = array();

	/**
	 * @var array Persistent relevant post IDs after various filtration
	 * @since 1.8
	 */
	private $relevant_post_ids = array();

	/**
	 * @var string Core database prefix
	 * @since 1.8
	 */
	private $db_prefix;

	/**
	 * @var string The main search query
	 * @since 1.8
	 */
	private $sql;

	/**
	 * @var string Arbitrary status SQL for the main query
	 * @since 1.8
	 */
	private $sql_status;

	/**
	 * @var string JOIN SQL used throughout the query
	 * @since 1.8
	 */
	private $sql_join;

	/**
	 * @var string Arbitrary SQL conditions used throughout the query
	 * @since 1.8
	 */
	private $sql_conditions;

	/**
	 * @var string Arbitrary WHERE clause used throughout the query
	 * @since 1.8
	 */
	private $sql_term_where;

	/**
	 * @var string Generated SQL based on post IDs to include
	 * @since 1.8
	 */
	private $sql_include;

	/**
	 * @var string Generated SQL based on post IDs to exclude
	 * @since 1.8
	 */
	private $sql_exclude;



	/**
	 * Constructor
	 *
	 * @param array $args
	 * @since 1.0
	 */
	function __construct( $args = array() )
	{
		global $wpdb, $searchwp;

		do_action( 'searchwp_log', 'SearchWPSearch __construct()' );

		$defaults = array(
			'terms'             => '',
			'engine'            => 'default',
			'page'              => 1,
			'posts_per_page'    => intval( get_option( 'posts_per_page' ) ),
			'order'             => $this->order,
			'load_posts'        => true,
		);

		$this->db_prefix = $wpdb->prefix . SEARCHWP_DBPREFIX;

		// process our arguments
		$args = wp_parse_args( $args, $defaults );
		$this->searchwp = SearchWP::instance();

		// instantiate our stemmer for later
		$this->stemmer = new SearchWPStemmer();

		do_action( 'searchwp_log', '$args = ' . var_export( $args, true ) );

		// if we have a valid engine, perform the query
		if( $this->searchwp->isValidEngine( $args['engine'] ) ) {
			// this filter is also applied in the SearchWP class search methods
			// TODO: should this be applied in both places? which?
			$sanitizeTerms = apply_filters( 'searchwp_sanitize_terms', true, $args['engine'] );
			if ( ! is_bool( $sanitizeTerms ) ) {
				$sanitizeTerms = true;
			}

			// whitelist search terms
			$pre_whitelist_terms = is_array( $args['terms'] ) ? implode( ' ', $args['terms'] ) : ' ' . $args['terms'] . ' ';
			$whitelisted_terms = $this->searchwp->extract_terms_using_pattern_whitelist( $pre_whitelist_terms, false );

			if( $sanitizeTerms ) {
				$terms = $this->searchwp->sanitizeTerms( $args['terms'] );
			} else {
				$terms = $args['terms'];
				do_action( 'searchwp_log', 'Opted out of internal sanitization' );
			}

			if( is_array( $whitelisted_terms ) ) {
				$whitelisted_terms = array_filter( array_map( 'trim', $whitelisted_terms ), 'strlen' );
			}

			if( is_array( $terms ) ) {
				$terms = array_filter( array_map( 'trim', $terms ), 'strlen' );
				$terms = array_merge( $terms, $whitelisted_terms );
			} else {
				$terms .= ' ' . implode( ' ', $whitelisted_terms );
			}

			// make sure the terms are unique, especially after whitelist matching
			if( is_array( $terms ) ) {
				$terms = array_unique( $terms );
				$terms = array_filter( $terms, 'strlen' );
			}

			$engine = $args['engine'];

			// allow dev to customize post statuses are included
			$this->post_statuses = (array) apply_filters( 'searchwp_post_statuses', $this->post_statuses, $engine );
			foreach( $this->post_statuses as $post_status_key => $post_status_value ) {
				$this->post_statuses[$post_status_key] = sanitize_key( $post_status_value );
			}

			do_action( 'searchwp_log', '$terms = ' . var_export( $terms, true ) );

			if( strtoupper( apply_filters( 'searchwp_search_query_order', $args['order'] ) ) != 'DESC' && strtoupper( $args['order'] ) != 'ASC' ) {
				$args['order'] = 'DESC';
			}

			// filter the terms just before querying
			$terms = apply_filters( 'searchwp_pre_search_terms', $terms, $engine );

			do_action( 'searchwp_log', 'searchwp_pre_search_terms $terms = ' . var_export( $terms, true ) );

			$this->terms        = $terms;
			$this->engine       = $engine;
			$this->settings     = empty( $searchwp) ? get_option( SEARCHWP_PREFIX . 'settings' ) : $searchwp->settings;
			$this->page         = absint( $args['page'] );
			$this->postsPer     = intval( $args['posts_per_page'] );
			$this->order        = $args['order'];
			$this->load_posts   = is_bool( $args['load_posts'] ) ? $args['load_posts'] : true;

			// perform our query
			$this->posts = $this->query();
		}

	}


	/**
	 * Perform a query on the index
	 *
	 * @return array Posts returned by the query
	 * @since 1.0
	 */
	function query()
	{
		do_action( 'searchwp_log', 'query()' );

		do_action( 'searchwp_before_query_index', array(
				'terms'     => $this->terms,
				'engine'    => $this->engine,
				'settings'  => $this->settings,
				'page'      => $this->page,
				'postsPer'  => $this->postsPer
			) );

		$this->queryForPostIDs();

		$swpargs = array(
			'terms'     => $this->terms,
			'engine'    => $this->engine,
			'settings'  => $this->settings,
			'page'      => $this->page,
			'postsPer'  => $this->postsPer
		);

		do_action( 'searchwp_after_query_index', $swpargs );

		// facilitate filtration of returned results
		$this->postIDs = apply_filters( 'searchwp_query_results', $this->postIDs, $swpargs );

		if( empty( $this->postIDs ) ) {
			return array();
		}

		// our post IDs will have already been filtered based on the engine settings, so we want to query for
		// anything that matches our post IDs
		$args = array(
			'posts_per_page'    => count( $this->postIDs ),
			'post_type'         => 'any',
			'post_status'       => 'any',	// we've already filtered our post statuses in the original query
			'post__in'          => $this->postIDs,
			'orderby'           => 'post__in'
		);

		// we want ints all the time
		$this->postIDs = array_map( 'absint', $this->postIDs );

		if ( $this->load_posts && true === apply_filters( 'searchwp_load_posts', true, $swpargs ) ) {
			$posts = apply_filters( 'searchwp_found_post_objects', get_posts( $args ), $swpargs );
		} else {
			$posts = $this->postIDs;
		}

		return $posts;
	}


	/**
	 * Ensures that all post types in settings still exist
	 *
	 * @since 1.8
	 */
	private function validate_post_types() {
		if( is_array( $this->searchwp->postTypes ) ) {
			foreach( $this->engineSettings as $postType => $postTypeSettings ) {
				if( !in_array( $postType, $this->searchwp->postTypes ) ) {
					unset( $this->engineSettings[$postType] );
				}
			}
		}
	}


	/**
	 * Determine whether any post types are enabled
	 *
	 * @return bool Whether there are any enabled post types
	 * @since 1.8
	 */
	private function any_enabled_post_types() {
		$enabled_post_type = false;

		// check to make sure that at least one post type is enabled for this engine
		if( is_array( $this->engineSettings ) ) {
			foreach( $this->engineSettings as $postType => $postTypeWeights ) {
				if( isset( $postTypeWeights['enabled'] ) && $postTypeWeights['enabled'] == true ) {
					$enabled_post_type = true;
					break;
				}
			}
		}

		return $enabled_post_type;
	}


	/**
	 * Set excluded IDs as per the engine settings
	 *
	 * @since 1.8
	 */
	private function set_excluded_ids_from_settings() {
		$excludeIDs = apply_filters( 'searchwp_prevent_indexing', array() ); // catch anything that shouldn't have been indexed anyway
		foreach( $this->engineSettings as $postType => $postTypeWeights ) {
			// store our exclude clause
			if( isset( $postTypeWeights['options']['exclude'] ) && ! empty( $postTypeWeights['options']['exclude'] ) ) {
				$postTypeExcludeIDs = $postTypeWeights['options']['exclude'];
				if( is_string( $postTypeExcludeIDs ) && false !== strpos( $postTypeExcludeIDs, ',' ) ) {
					$postTypeExcludeIDs = explode( ',', $postTypeExcludeIDs );
				} else {
					if( is_string( $postTypeExcludeIDs ) ) {
						$postTypeExcludeIDs = array( absint( $postTypeExcludeIDs ) );
					} else {
						$postTypeExcludeIDs = array();
					}
				}
			} else {
				$postTypeExcludeIDs = array();
			}

			if( ! empty( $postTypeExcludeIDs ) && is_array( $postTypeExcludeIDs ) ) {
				foreach( $postTypeExcludeIDs as $postTypeExcludeID ) {
					$excludeIDs[] = absint( $postTypeExcludeID );
				}
			}
		}

		if( ! is_array( $excludeIDs ) ) {
			$excludeIDs = array();
		} else {
			$excludeIDs = array_map( 'absint', $excludeIDs );
		}

		do_action( 'searchwp_log', '$excludeIDs = ' . var_export( $excludeIDs, true ) );
		$this->excluded = $excludeIDs;
	}


	/**
	 * Set excluded IDs based on taxonomy terms in the settings
	 *
	 * @since 1.8
	 */
	private function set_excluded_ids_from_taxonomies() {
		add_filter( 'searchwp_force_wp_query', '__return_true' ); // we're going to be firing a WP_Query and want it to finish
		foreach( $this->engineSettings as $postType => $postTypeWeights ) {
			$taxonomies = get_object_taxonomies( $postType );
			if( is_array( $taxonomies ) && count( $taxonomies ) ) {
				foreach( $taxonomies as $taxonomy ) {
					$taxonomy = get_taxonomy( $taxonomy );
					if( isset( $postTypeWeights['options']['exclude_' . $taxonomy->name] ) ) {
						$excludedTerms = explode( ',', $postTypeWeights['options']['exclude_' . $taxonomy->name] );

						if( !is_array( $excludedTerms ) ) {
							$excludedTerms = array( intval( $excludedTerms ) );
						}

						if( !empty( $excludedTerms ) ) {
							foreach( $excludedTerms as $excludedKey => $excludedValue ) {
								$excludedTerms[$excludedKey] = intval( $excludedValue );
							}
						}

						// determine which post(s) have this term
						$args = array(
							'posts_per_page'    => -1,
							'fields'            => 'ids',
							'post_type'         => $postType,
							'suppress_filters'  => true,
							'tax_query'         => array(
								array(
									'taxonomy'  => $taxonomy->name,
									'field'     => 'id',
									'terms'     => $excludedTerms
								)
							)
						);

						$excludedByTerm = new WP_Query( $args );

						if( !empty( $excludedByTerm ) ) {
							$this->excluded = array_merge( $this->excluded, $excludedByTerm->posts );
						}
					}
				}
			}
		}
		remove_filter( 'searchwp_force_wp_query', '__return_true' );

		do_action( 'searchwp_log', 'After taxonomy exclusion $excludeIDs = ' . var_export( $this->excluded, true ) );
	}


	/**
	 * Determine which field types should be considered for AND logic
	 *
	 * @since 1.8
	 */
	private function get_and_fields() {
		// allow devs to filter which fields should be included for AND checks
		$andFieldsDefaults = array( 'title', 'content', 'slug', 'excerpt', 'comment', 'tax', 'meta' );
		$andFields = apply_filters( 'searchwp_and_fields', $andFieldsDefaults );

		// validate AND fields
		if( is_array( $andFields ) && !empty( $andFields ) ) {
			$andFields = array_map( 'strtolower', $andFields );
			foreach( $andFields as $andFieldKey => $andField ) {
				if( !in_array( $andField, $andFieldsDefaults ) ) {
					// invalid field, kill it
					unset( $andFields[$andFieldKey] );
				}
			}
		} else {
			// returned not an array, so reset it (which will basically invalidate AND searching)
			$andFields = array();
		}

		do_action( 'searchwp_log', '$andFields = ' . var_export( $andFields, true ) );

		return $andFields;
	}


	/**
	 * Use AND logic to find post IDs that have all search terms
	 *
	 * @param $andFields array The AND fields to consider when applying logic
	 * @param $andTerm string The keyword
	 *
	 * @return array The applicable Post IDs
	 * @since 1.8
	 */
	private function get_post_ids_via_and( $andFields, $andTerm ) {

		global $wpdb;

		// we're going to utilize $andFields to build our query based on what the dev wants to count for AND queries
		$andFieldsCoalesce = $this->get_and_field_coalesce( $andFields );

		// in order to save having to scrub through every enabled post type
		// we're just going to assume a stem here and limit the result pool as quickly as possible
		// since the main query will take into consideration the additional limitation of the stem

		$unstemmed = $andTerm;
		$maybeStemmed = apply_filters( 'searchwp_custom_stemmer', $unstemmed );

		// if the term was stemmed via the filter use it, else generate our own
		$andTerm = ( $unstemmed == $maybeStemmed ) ? $this->stemmer->stem( $andTerm ) : $maybeStemmed;

		$andTerm = $wpdb->prepare( '%s', $andTerm );
		$relevantTermWhere = " {$this->db_prefix}terms.stem = " . strtolower( $andTerm );

		// as an optimization we're going to break up this query into three 'parts'
		//  1) SELECT against the index table to find out where this term appears at least once
		//  2) SELECT against the cf table
		//  3) SELECT against the tax table
		//
		// all three will be UNIONed but all three are also filterable so we need to build this query carefully
		// and completely based on $andFields (which is an array of fields to consider)

		$andTermSQL = "";

		// first SQL segment is against the index table
		if ( ! empty( $andFieldsCoalesce ) ) {
			// we do in fact want to run query 1
			$andTermSQL .= "
				SELECT {$this->db_prefix}index.post_id,
				       {$andFieldsCoalesce} as termcount
				FROM {$this->db_prefix}index FORCE INDEX (termindex)
				LEFT JOIN {$this->db_prefix}terms
					ON {$this->db_prefix}index.term = {$this->db_prefix}terms.id
				WHERE {$relevantTermWhere}
				GROUP BY {$this->db_prefix}index.post_id
				HAVING termcount > 0";
		}

		// next SQL segment is against the cf table
		if ( ! empty( $andTermSQL ) ) {
			$andTermSQL .= " UNION ";
		}
		if ( in_array( 'meta', $andFields ) ) {
			// we want to apply AND logic to the cf table
			$andTermSQL .= "
				SELECT {$this->db_prefix}cf.post_id, count as termcount
				FROM {$this->db_prefix}cf FORCE INDEX (term)
				LEFT JOIN {$this->db_prefix}terms
					ON {$this->db_prefix}cf.term = {$this->db_prefix}terms.id
				WHERE {$relevantTermWhere}
				GROUP BY {$this->db_prefix}cf.post_id";
		}

		// last SQL segment is against the tax table
		if ( ! empty( $andTermSQL ) ) {
			$andTermSQL .= " UNION ";
		}
		if ( in_array( 'tax', $andFields ) ) {
			// we want to apply AND logic to the cf table
			$andTermSQL .= "
				SELECT {$this->db_prefix}tax.post_id, count as termcount
				FROM {$this->db_prefix}tax FORCE INDEX (term)
				LEFT JOIN {$this->db_prefix}terms
					ON {$this->db_prefix}tax.term = {$this->db_prefix}terms.id
				WHERE {$relevantTermWhere}
				GROUP BY {$this->db_prefix}tax.post_id";
		}

		$postsWithTermPresent = $wpdb->get_col( $andTermSQL );

		// even though we're using UNION, we will likely have duplicate post_ids because each row will have different term counts
		if( is_array( $postsWithTermPresent ) && ! empty( $postsWithTermPresent ) ) {
			$postsWithTermPresent = array_unique( $postsWithTermPresent );
		}

		return $postsWithTermPresent;
	}


	/**
	 * Generate the SQL used in AND field logic
	 *
	 * @param $andFields array The AND fields to consider when applying logic
	 *
	 * @return string SQL to use in the main query
	 * @since 1.8
	 */
	private function get_and_field_coalesce( $andFields ) {
		$coalesceFields = array();

		// we're going to utilize $andFields to build our query based on what the dev wants to count for AND queries
		foreach( $andFields as $andField ) {
			switch( $andField ) {
				case 'tax':
					// taxonomy has been broken out into UNION as of version 2.0.5
					break;
				case 'meta':
					// cf has been broken out into UNION as of 2.0.5
					break;
				default:
					$andFieldTable = 'index';
					$andFieldColumn = $andField;
					break;
			}

			$coalesceFields[] = "COALESCE({$this->db_prefix}{$andFieldTable}.{$andFieldColumn},0)";
		}
		$andFieldsCoalesce = implode( ' + ', $coalesceFields );
		return $andFieldsCoalesce;
	}


	/**
	 * If applicable, limit posts using AND logic
	 *
	 * @since 1.8
	 */
	private function maybe_do_and_logic() {
		$relevantPostIds = array();

		// AND logic only applies if there's more than one term (and the dev doesn't disable it)
		$doAnd = ( count( $this->terms ) > 1 && apply_filters( 'searchwp_and_logic', true ) ) ? true : false;
		do_action( 'searchwp_log', '$doAnd = ' . var_export( $doAnd, true ) );

		$andFields = $this->get_and_fields();

		if( $doAnd && is_array( $andFields ) && !empty( $andFields ) ) {
			$andTerms = array();
			$applicableAndResults = true;

			// grab posts with each term in all applicable AND fields
			foreach( $this->terms as $andTerm ) {

				$postsWithTermPresent = $this->get_post_ids_via_and( $andFields, $andTerm );

				do_action( 'searchwp_log', '$postsWithTermPresent = ' . var_export( $postsWithTermPresent, true ) );

				if( !empty( $postsWithTermPresent ) ) {
					$andTerms[] = $postsWithTermPresent;
				} else {
					// since no posts were found with this term in the title, our AND logic fails
					$applicableAndResults = false;
					break;
				}
			}

			// find the common post IDs across the board
			if( $applicableAndResults ) {
				$relevantPostIds = call_user_func_array( 'array_intersect', $andTerms );
			}

		}

		// we want ints, always
		$this->relevant_post_ids = array_map( 'absint', $relevantPostIds );
	}


	/**
	 * If a weight is < 0 any results need to be forcefully excluded
	 *
	 * @since 1.8
	 */
	private function exclude_posts_by_weight() {
		global $wpdb;

		// we need to check for exclusions at this point (weights of < zero)
		$andTerms = array();
		foreach( $this->engineSettings as $postType => $postTypeWeights ) {
			if( isset( $postTypeWeights['enabled'] ) && $postTypeWeights['enabled'] == true ) {
				foreach( $postTypeWeights['weights'] as $type => $weight ) {
					foreach( $this->terms as $andTerm ) {
						$applicableExclusion = false;

						// determine whether we want a term match or stem match
						if( !isset( $postTypeWeights['options']['stem'] ) || empty( $postTypeWeights['options']['stem'] ) ) {
							$relavantTermWhere = " {$this->db_prefix}terms.term = " . strtolower( $wpdb->prepare( '%s', $andTerm ) );
						} else {
							$unstemmed = $andTerm;
							$maybeStemmed = apply_filters( 'searchwp_custom_stemmer', $unstemmed );

							// if the term was stemmed via the filter use it, else generate our own
							$andTerm = ( $unstemmed == $maybeStemmed ) ? $this->stemmer->stem( $andTerm ) : $maybeStemmed;

							$relavantTermWhere = " {$this->db_prefix}terms.stem = " . strtolower( $wpdb->prepare( '%s', $andTerm ) );
						}

						$andInternalSQL = "
									SELECT {$this->db_prefix}index.post_id
									FROM {$this->db_prefix}index
									LEFT JOIN {$this->db_prefix}terms
									ON {$this->db_prefix}index.term = {$this->db_prefix}terms.id
									LEFT JOIN {$this->db_prefix}cf
									ON {$this->db_prefix}index.post_id = {$this->db_prefix}cf.post_id
									LEFT JOIN {$this->db_prefix}tax
									ON {$this->db_prefix}index.post_id = {$this->db_prefix}tax.post_id
									WHERE {$relavantTermWhere} ";

						if( !empty( $relevantPostIds ) ) {
							$relevantIDsSQL = implode( ",", $relevantPostIds );
							$andInternalSQL .= " AND {$this->db_prefix}index.post_id IN ({$relevantIDsSQL}) ";
						}

						$andInternalSQL .= " AND ( ";

						// $weight will sometimes be an array (taxonomies and custom fields)
						if( !is_array( $weight ) && intval( $weight ) < 0 ) {
							$applicableExclusion = true;
							switch( $type ) {
								case 'title':
									$andInternalSQL .= " {$this->db_prefix}index.title > 0  OR ";
									break;
								case 'content':
									$andInternalSQL .= " {$this->db_prefix}index.content > 0  OR ";
									break;
								case 'slug':
									$andInternalSQL .= " {$this->db_prefix}index.slug > 0  OR ";
									break;
								case 'excerpt':
									$andInternalSQL .= " {$this->db_prefix}index.excerpt > 0  OR ";
									break;
								case 'comment':
									$andInternalSQL .= " {$this->db_prefix}index.comment > 0  OR ";
									break;
							}
						} else {
							// it's either a taxonomy or custom field, so we need to handle it a bit differently
							if( $type == 'tax' ) {
								foreach( $weight as $postTypeTax => $postTypeTaxWeight ) {
									if( intval( $postTypeTaxWeight ) < 0 ) {
										$applicableExclusion = true;
										$andInternalSQL .= " ( {$this->db_prefix}tax.taxonomy = '{$postTypeTax}' AND {$this->db_prefix}tax.count > 0 )  OR ";
									}
								}
							} elseif( $type == 'cf' ) {
								foreach( $weight as $postTypeCustomField ) {
									foreach( $postTypeCustomField as $postTypeCustomFieldKey => $postTypeCustomFieldWeight ) {
										if( intval( $postTypeCustomFieldWeight ) < 0 ) {
											$applicableExclusion = true;
											$andInternalSQL .= " ( {$this->db_prefix}cf.metakey = '{$postTypeCustomFieldKey}' AND {$this->db_prefix}cf.count > 0 )  OR ";
										}
									}
								}
							}
						}

						// trim off the extra OR
						$andInternalSQL = substr( $andInternalSQL, 0, strlen( $andInternalSQL ) - 4 ) . " ) GROUP BY {$this->db_prefix}index.post_id";

						// if this exclusion is applicable, grab post IDs that trigger the exclusion
						if( $applicableExclusion ) {
							$postsWithTerm = $wpdb->get_col( $andInternalSQL );

							// add these post IDs to the heap (we're going to make it unique later)
							$andTerms = array_merge( $andTerms, array_map( 'absint', $postsWithTerm ) );
						}
					}
				}
			}
		}

		// $andTerms is a conglomerate pile of post IDs violating the exclusion rule
		$andTerms = array_unique( $andTerms );

		// merge the weight-based exlusions on to the main excludes
		$excludeIDs = array_merge( $this->excluded, $andTerms );

		// make sure everything is an int
		if( !empty( $excludeIDs ) ) {
			$excludeIDs = array_map( 'absint', $excludeIDs );
		}

		$this->excluded = $excludeIDs;
	}


	/**
	 * Find posts that meet AND logic limitations in the title only
	 *
	 * @return array|mixed Applicable Post IDs
	 * @since 1.8
	 */
	private function get_post_ids_via_and_in_title() {
		global $wpdb;

		// find posts where all terms appear in the title
		$andTerms = array();
		$applicableAndResults = true;
		$relevantPostIds = $this->relevant_post_ids;

		$intermediateIncludeSQL = ( !empty( $this->relevant_post_ids ) ) ? " AND {$this->db_prefix}index.post_id IN (" . implode( ',', $this->relevant_post_ids ) . ") " : '';
		$intermediateExcludeSQL = ( !empty( $this->excluded ) ) ? " AND {$this->db_prefix}index.post_id NOT IN (" . implode( ',', $this->excluded ) . ") " : '';

		// grab posts with each term in the title
		foreach( $this->terms as $andTerm ) {
			// determine whether we want a term match or stem match
			$andTerm = $wpdb->prepare( '%s', $andTerm );
			if( !isset( $postTypeWeights['options']['stem'] ) || empty( $postTypeWeights['options']['stem'] ) ) {
				$relavantTermWhere = " {$this->db_prefix}terms.term = " . strtolower( $wpdb->prepare( '%s', $andTerm ) );
			} else {
				$unstemmed = $andTerm;
				$maybeStemmed = apply_filters( 'searchwp_custom_stemmer', $unstemmed );

				// if the term was stemmed via the filter use it, else generate our own
				$andTerm = ( $unstemmed == $maybeStemmed ) ? $this->stemmer->stem( $andTerm ) : $maybeStemmed;

				$relavantTermWhere = " {$this->db_prefix}terms.stem = " . strtolower( $wpdb->prepare( '%s', $andTerm ) );
			}

			$postsWithTermInTitle = $wpdb->get_col(
			"SELECT post_id
				FROM {$this->db_prefix}index
				LEFT JOIN {$this->db_prefix}terms
				ON {$this->db_prefix}index.term = {$this->db_prefix}terms.id
				WHERE {$relavantTermWhere}
				{$intermediateExcludeSQL}
				{$intermediateIncludeSQL}
				AND {$this->db_prefix}index.title > 0"
			);

			if( !empty( $postsWithTermInTitle ) ) {
				$andTerms[] = $postsWithTermInTitle;
			} else {
				// since no posts were found with this term in the title, our AND logic fails
				$applicableAndResults = false;
				break;
			}
		}

		// find the common post IDs across the board
		if( $applicableAndResults ) {
			$relevantPostIds = call_user_func_array( 'array_intersect', $andTerms );
		}

		return $relevantPostIds;
	}


	/**
	 * Opens the main query SQL
	 *
	 * @since 1.8
	 */
	private function query_open() {
		global $wpdb;
		$this->sql = "SELECT SQL_CALC_FOUND_ROWS {$wpdb->prefix}posts.ID AS post_id, ";
	}


	/**
	 * Generate the SQL that calculates overall weight for a post type for a search term
	 *
	 * @since 1.8
	 */
	private function query_sum_post_type_weights() {
		// sum our final weights per post type
		foreach( $this->engineSettings as $postType => $postTypeWeights ) {
			if( isset( $postTypeWeights['enabled'] ) && $postTypeWeights['enabled'] == true ) {
				$termCounter = 1;
				if( empty( $postTypeWeights['options']['attribute_to'] ) ) {
					foreach( $this->terms as $term ) {
						$this->sql .= "COALESCE(term{$termCounter}.`{$postType}weight`,0) + ";
						$termCounter++;
					}
				} else {
					foreach( $this->terms as $term ) {
						$this->sql .= "COALESCE(term{$termCounter}.`{$postType}attr`,0) + ";
						$termCounter++;
					}
				}
				$this->sql = substr( $this->sql, 0, strlen( $this->sql ) - 2 ); // trim off the extra +
				$this->sql .= " AS `final{$postType}weight`, ";
			}
		}
	}


	/**
	 * Generate the SQL that calculates the overall weight for a search term
	 *
	 * @since 1.8
	 */
	private function query_sum_final_weight() {
		global $wpdb;
		// build our final, overall weight
		foreach( $this->engineSettings as $postType => $postTypeWeights ) {
			if( isset( $postTypeWeights['enabled'] ) && $postTypeWeights['enabled'] == true ) {
				$termCounter = 1;
				if( empty( $postTypeWeights['options']['attribute_to'] ) ) {
					foreach( $this->terms as $term ) {
						$this->sql .= "COALESCE(term{$termCounter}.`{$postType}weight`,0) + ";
						$termCounter++;
					}
				} else {
					foreach( $this->terms as $term ) {
						$this->sql .= "COALESCE(term{$termCounter}.`{$postType}attr`,0) + ";
						$termCounter++;
					}
				}
			}
		}

		$this->sql = substr( $this->sql, 0, strlen( $this->sql ) - 2 ); // trim off the extra +
		$this->sql .= " AS finalweight FROM {$wpdb->prefix}posts ";
	}


	/**
	 * Generate the SQL that defines post type weight
	 *
	 * @since 1.8
	 */
	private function query_post_type_weight() {
		foreach( $this->engineSettings as $postType => $postTypeWeights ) {
			if( isset( $postTypeWeights['enabled'] ) && $postTypeWeights['enabled'] == true && empty( $postTypeWeights['options']['attribute_to'] ) ) {
				$this->sql .= ", COALESCE(`{$postType}weight`,0) AS `{$postType}weight` ";
			}
		}
	}


	/**
	 * Generate the SQL that defines attributed post type weight
	 *
	 * @since 1.8
	 */
	private function query_post_type_attributed() {
		foreach( $this->engineSettings as $postType => $postTypeWeights ) {
			if( isset( $postTypeWeights['enabled'] ) && $postTypeWeights['enabled'] == true && !empty( $postTypeWeights['options']['attribute_to'] ) ) {
				$attributedTo = intval( $postTypeWeights['options']['attribute_to'] );
				// make sure we're not excluding the attributed post id
				if( ! in_array( $attributedTo, $this->excluded ) ) {
					$this->sql .= ", COALESCE(`{$postType}attr`,0) as `{$postType}attr` ";
				}
			}
		}
	}


	/**
	 * Generate the SQL that totals the post weight totals
	 *
	 * @since 1.8
	 */
	private function query_post_type_weight_total() {
		foreach( $this->engineSettings as $postType => $postTypeWeights ) {
			if( isset( $postTypeWeights['enabled'] ) && $postTypeWeights['enabled'] == true && empty( $postTypeWeights['options']['attribute_to'] ) ) {
				$this->sql .= " COALESCE(`{$postType}weight`,0) +";
			}
		}
	}


	/**
	 * Generate the SQL that totals the attributed post weight totals
	 *
	 * @since 1.8
	 */
	private function query_post_type_attributed_total() {
		foreach( $this->engineSettings as $postType => $postTypeWeights ) {
			if( isset( $postTypeWeights['enabled'] ) && $postTypeWeights['enabled'] == true && !empty( $postTypeWeights['options']['attribute_to'] ) ) {
				$attributedTo = intval( $postTypeWeights['options']['attribute_to'] );
				// make sure we're not excluding the attributed post id
				if( ! in_array( $attributedTo, $this->excluded ) ) {
					$this->sql .= " COALESCE(`{$postType}attr`,0) +";
				}
			}
		}
	}


	/**
	 * Generate the SQL that opens the per-term sub-query
	 *
	 * @since 1.8
	 */
	private function query_open_term() {
		global $wpdb;

		$this->sql .= "LEFT JOIN ( ";

		// our final query cap
		$this->sql .= "SELECT {$wpdb->prefix}posts.ID AS post_id ";

		// implement our post type weight column
		$this->query_post_type_weight();

		// implement our post type attributed weight column
		$this->query_post_type_attributed();

		$this->sql .= " , ";

		// concatenate our total weight with post type weight
		$this->query_post_type_weight_total();

		// concatenate our total weight with our attributed weight
		$this->query_post_type_attributed_total();

		$this->sql = substr( $this->sql, 0, strlen( $this->sql ) - 2 );	// trim off the extra +

		$this->sql .= " AS weight ";
		$this->sql .= " FROM {$wpdb->prefix}posts ";
	}


	/**
	 * Limit results pool by mime type
	 *
	 * @param $mimes array Mime types to include
	 * @since 1.8
	 */
	private function query_limit_by_mimes( $mimes ) {
		global $wpdb;
		$targetedMimes  = array();

		// TODO: Better system for this
		$mimeref = array(
			'image' => array(
				'image/jpeg',
				'image/gif',
				'image/png',
				'image/bmp',
				'image/tiff',
				'image/x-icon',
			),
			'video' => array(
				'video/x-ms-asf',
				'video/x-ms-wmv',
				'video/x-ms-wmx',
				'video/x-ms-wm',
				'video/avi',
				'video/divx',
				'video/x-flv',
				'video/quicktime',
				'video/mpeg',
				'video/mp4',
				'video/ogg',
				'video/webm',
				'video/x-matroska',
			),
			'text' => array(
				'text/plain',
				'text/csv',
				'text/tab-separated-values',
				'text/calendar',
				'text/richtext',
				'text/css',
				'text/html',
			),
			'audio' => array(
				'audio/mpeg',
				'audio/x-realaudio',
				'audio/wav',
				'audio/ogg',
				'audio/midi',
				'audio/x-ms-wma',
				'audio/x-ms-wax',
				'audio/x-matroska',
			),
			'application' => array(
				'application/rtf',
				'application/javascript',
				'application/pdf',
				'application/x-shockwave-flash',
				'application/java',
				'application/x-tar',
				'application/zip',
				'application/x-gzip',
				'application/rar',
				'application/x-7z-compressed',
				'application/x-msdownload',
			),
			'msoffice' => array(
				'application/msword',
				'application/vnd.ms-powerpoint',
				'application/vnd.ms-write',
				'application/vnd.ms-excel',
				'application/vnd.ms-access',
				'application/vnd.ms-project',
				'application/vnd.openxmlformats-officedocument.wordprocessingml. document',
				'application/vnd.ms-word.document.macroEnabled.12',
				'application/vnd.openxmlformats-officedocument.wordprocessingml. template',
				'application/vnd.ms-word.template.macroEnabled.12',
				'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				'application/vnd.ms-excel.sheet.macroEnabled.12',
				'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
				'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
				'application/vnd.ms-excel.template.macroEnabled.12',
				'application/vnd.ms-excel.addin.macroEnabled.12',
				'application/vnd.openxmlformats-officedocument.presentationml. presentation',
				'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
				'application/vnd.openxmlformats-officedocument.presentationml. slideshow',
				'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
				'application/vnd.openxmlformats-officedocument.presentationml.template',
				'application/vnd.ms-powerpoint.template.macroEnabled.12',
				'application/vnd.ms-powerpoint.addin.macroEnabled.12',
				'application/vnd.openxmlformats-officedocument.presentationml.slide',
				'application/vnd.ms-powerpoint.slide.macroEnabled.12',
				'application/onenote',
			),
			'openoffice' => array(
				'application/vnd.oasis.opendocument.text',
				'application/vnd.oasis.opendocument.presentation',
				'application/vnd.oasis.opendocument.spreadsheet',
				'application/vnd.oasis.opendocument.graphics',
				'application/vnd.oasis.opendocument.chart',
				'application/vnd.oasis.opendocument.database',
				'application/vnd.oasis.opendocument.formula',
			),
			'wordperfect' => array(
				'application/wordperfect',
			),
			'iwork' => array(
				'application/vnd.apple.keynote',
				'application/vnd.apple.numbers',
				'application/vnd.apple.pages',
			),
		);

		foreach( $mimes as $mimeKey )
		{
			switch( intval( $mimeKey ) )
			{
				case 1: // PDFs
					$targetedMimes = array_merge( $targetedMimes, array( 'application/pdf' ) );
					break;
				case 2: // Plain Text
					$targetedMimes = array_merge( $targetedMimes, $mimeref['text'] );
					break;
				case 3: // Images
					$targetedMimes = array_merge( $targetedMimes, $mimeref['images'] );
					break;
				case 4: // Video
					$targetedMimes = array_merge( $targetedMimes, $mimeref['video'] );
					break;
				case 5: // Audio
					$targetedMimes = array_merge( $targetedMimes, $mimeref['audio'] );
					break;
				default: // All Documents
					$targetedMimes = array_merge( $targetedMimes,
						$mimeref['text'],
						$mimeref['application'],
						$mimeref['msoffice'],
						$mimeref['openoffice'],
						$mimeref['wordperfect'],
						$mimeref['iwork']
					);
					break;
			}

			// remove dupes
			$targetedMimes = array_unique( $targetedMimes );
		}

		// we have an array of keys that match MIME types (not subtypes) that we can limit to by appending this condition
		$this->sql_status .= " AND {$wpdb->prefix}posts.post_type = 'attachment' AND {$wpdb->prefix}posts.post_mime_type IN ( '" . implode( "', '", $targetedMimes ) . "' ) ";
	}


	/**
	 * Generate the SQL that totals Custom Field weight
	 *
	 * @param $weights array Custom Field weights from SearchWP settings
	 *
	 * @return string SQL to use in the main query
	 * @since 1.8
	 */
	private function query_coalesce_custom_fields( $weights) {
		$coalesceCustomFields = '0 +';
		if( isset( $weights ) && is_array( $weights ) && !empty( $weights ) ) {

			// first we'll try to merge any matching weight meta_keys so as to save as many JOINs as possible
			$optimized_weights = array();
			$like_weights = array();
			foreach( $weights as $post_type_meta_guid => $post_type_custom_field ) {
				$custom_field_weight = absint( $post_type_custom_field['weight'] );
				$post_type_custom_field_key = $post_type_custom_field['metakey'];

				// allow developers to implement LIKE matching on custom field keys
				if( false == strpos( $post_type_custom_field_key, '%' ) ) {
					$optimized_weights[$custom_field_weight][] = $post_type_custom_field_key;
				} else {
					$like_weights[] = array(
						'metakey'   => $post_type_custom_field_key,
						'weight'    => $custom_field_weight
					);
				}
			}

			$totalCustomFields = count( $optimized_weights ) + count( $like_weights );
			for( $i = 0; $i < $totalCustomFields; $i++ ) {
				$coalesceCustomFields .= " COALESCE(cfweight" . $i . ",0) + ";
			}
		}
		$coalesceCustomFields = substr( $coalesceCustomFields, 0, strlen( $coalesceCustomFields) - 2 );
		return $coalesceCustomFields;
	}


	/**
	 * Generate the SQL that totals taxonomy weight
	 *
	 * @param $weights array Taxonomy weights from SearchWP settings
	 *
	 * @return string SQL to use in the main query
	 * @since 1.8
	 */
	private function query_coalesce_taxonomies( $weights ) {
		$coalesceTaxonomies = '0 +';
		if( isset( $weights ) && is_array( $weights ) && !empty( $weights ) ) {

			// first we'll try to merge any matching weight taxonomies so as to save as many JOINs as possible
			$optimized_weights = array();
			foreach( $weights as $taxonomy_name => $taxonomy_weight ) {
				$taxonomy_weight = absint( $taxonomy_weight );
				$optimized_weights[$taxonomy_weight][] = $taxonomy_name;
			}

			$totalTaxonomies = count( $optimized_weights );
			for( $i = 0; $i < $totalTaxonomies; $i++ ) {
				$coalesceTaxonomies .= " COALESCE(taxweight" . $i . ",0) + ";
			}
		}
		$coalesceTaxonomies = substr( $coalesceTaxonomies, 0, strlen( $coalesceTaxonomies) - 2 );
		return $coalesceTaxonomies;
	}


	/**
	 * Generate the SQL used to open the per-post type sub-query
	 *
	 * @param $args array Arguments for the post type
	 * @since 1.8
	 */
	private function query_post_type_open( $args ) {
		global $wpdb;

		$defaults = array(
			'post_type'         => 'post',
			'post_column'       => 'ID',
			'title_weight'      => function_exists( 'searchwpGetEngineWeight' ) ? searchwpGetEngineWeight( 'title' ) : 20,
			'slug_weight'       => function_exists( 'searchwpGetEngineWeight' ) ? searchwpGetEngineWeight( 'slug' ) : 10,
			'content_weight'    => function_exists( 'searchwpGetEngineWeight' ) ? searchwpGetEngineWeight( 'content' ) : 2,
			'comment_weight'    => function_exists( 'searchwpGetEngineWeight' ) ? searchwpGetEngineWeight( 'comment' ) : 1,
			'excerpt_weight'    => function_exists( 'searchwpGetEngineWeight' ) ? searchwpGetEngineWeight( 'excerpt' ) : 6,
			'custom_fields'     => 0,
			'taxonomies'        => 0,
			'attributed_to'     => false,
		);

		// process our arguments
		$args = wp_parse_args( $args, $defaults );

		$this->sql .= "
			LEFT JOIN (
				SELECT {$wpdb->prefix}posts.{$args['post_column']} AS post_id,
					( {$this->db_prefix}index.title * {$args['title_weight']} ) +
					( {$this->db_prefix}index.slug * {$args['slug_weight']} ) +
					( {$this->db_prefix}index.content * {$args['content_weight']} ) +
					( {$this->db_prefix}index.comment * {$args['comment_weight']} ) +
					( {$this->db_prefix}index.excerpt * {$args['excerpt_weight']} ) +
					{$args['custom_fields']} + {$args['taxonomies']}";

		// the identifier is different if we're attributing
		$this->sql .= !empty( $args['attributed_to'] ) ? " AS `{$args['post_type']}attr` " : " AS `{$args['post_type']}weight` " ;

		$this->sql .= "
			FROM {$this->db_prefix}terms
			LEFT JOIN {$this->db_prefix}index ON {$this->db_prefix}terms.id = {$this->db_prefix}index.term
			LEFT JOIN {$wpdb->prefix}posts ON {$this->db_prefix}index.post_id = {$wpdb->prefix}posts.ID
			{$this->sql_join}
		";
	}


	/**
	 * Generate the SQL that extracts Custom Field weights
	 *
	 * @param $postType string The post type
	 * @param $weights array Custom Field weights from SearchWP Settings
	 * @since 1.8
	 */
	private function query_post_type_custom_field_weights( $postType, $weights ) {
		global $wpdb;

		$i = 0;

		// first we'll try to merge any matching weight meta_keys so as to save as many JOINs as possible
		$optimized_weights = array();
		$like_weights = array();
		foreach( $weights as $post_type_meta_guid => $post_type_custom_field ) {
			$custom_field_weight = absint( $post_type_custom_field['weight'] );
			$post_type_custom_field_key = $post_type_custom_field['metakey'];

			// allow developers to implement LIKE matching on custom field keys
			if( false == strpos( $post_type_custom_field_key, '%' ) ) {
				$optimized_weights[$custom_field_weight][] = $post_type_custom_field_key;
			} else {
				$like_weights[] = array(
					'metakey'   => $post_type_custom_field_key,
					'weight'    => $custom_field_weight
				);
			}
		}

		// our custom fields are now keyed by their weight, allowing us to group Custom Fields with the
		// same weight together in the same LEFT JOIN
		foreach( $optimized_weights as $weight_key => $meta_keys_for_weight ) {
			$post_meta_clause = '';
			if( ! in_array( 'searchwpcfdefault', str_ireplace( ' ', '', $meta_keys_for_weight ) ) ) {
				$post_meta_clause = " AND " . $this->db_prefix . "cf.metakey IN ('" . implode( "','", $meta_keys_for_weight ) . "')";
			}
			$this->sql .= "
				LEFT JOIN (
					SELECT {$this->db_prefix}cf.post_id, SUM({$this->db_prefix}cf.count * {$weight_key}) AS cfweight{$i}
					FROM {$this->db_prefix}terms
					LEFT JOIN {$this->db_prefix}cf ON {$this->db_prefix}terms.id = {$this->db_prefix}cf.term
					LEFT JOIN {$wpdb->prefix}posts ON {$this->db_prefix}cf.post_id = {$wpdb->prefix}posts.ID
					{$this->sql_join}
					WHERE {$this->sql_term_where}
					{$this->sql_status}
					AND {$wpdb->prefix}posts.post_type = '{$postType}'
					{$this->sql_exclude}
					{$this->sql_include}
					{$post_meta_clause}
					{$this->sql_conditions}
					GROUP BY {$this->db_prefix}cf.post_id
				) cfweights{$i} USING(post_id)";
			$i++;
		}

		// there also may be LIKE weights, though, so we need to build out that SQL as well
		if( !empty( $like_weights ) ) {
			foreach( $like_weights as $like_weight ) {
				$post_meta_clause = " AND " . $this->db_prefix . "cf.metakey LIKE '" . $like_weight['metakey'] . "'";
				$this->sql .= "
				LEFT JOIN (
					SELECT {$this->db_prefix}cf.post_id, SUM({$this->db_prefix}cf.count * {$like_weight['weight']}) AS cfweight{$i}
					FROM {$this->db_prefix}terms
					LEFT JOIN {$this->db_prefix}cf ON {$this->db_prefix}terms.id = {$this->db_prefix}cf.term
					LEFT JOIN {$wpdb->prefix}posts ON {$this->db_prefix}cf.post_id = {$wpdb->prefix}posts.ID
					{$this->sql_join}
					WHERE {$this->sql_term_where}
					{$this->sql_status}
					AND {$wpdb->prefix}posts.post_type = '{$postType}'
					{$this->sql_exclude}
					{$this->sql_include}
					{$post_meta_clause}
					{$this->sql_conditions}
					GROUP BY {$this->db_prefix}cf.post_id
				) cfweights{$i} USING(post_id)";
				$i++;
			}
		}

	}


	/**
	 * Generate the SQL that extracts taxonomy weights
	 *
	 * @param $postType string The post type
	 * @param $weights array Taxonomy weights from SearchWP Settings
	 * @since 1.8
	 */
	private function query_post_type_taxonomy_weights( $postType, $weights) {
		global $wpdb;

		$i = 0;

		// first we'll try to merge any matching weight taxonomies so as to save as many JOINs as possible
		$optimized_weights = array();
		foreach( $weights as $taxonomy_name => $taxonomy_weight ) {
			$taxonomy_weight = absint( $taxonomy_weight );
			$optimized_weights[$taxonomy_weight][] = $taxonomy_name;
		}

		foreach( $optimized_weights as $postTypeTaxWeight => $postTypeTaxonomies )
		{
			$postTypeTaxWeight = absint( $postTypeTaxWeight );
			$this->sql .= "
				LEFT JOIN (
					SELECT {$this->db_prefix}tax.post_id, SUM({$this->db_prefix}tax.count * {$postTypeTaxWeight}) AS taxweight{$i}
					FROM {$this->db_prefix}terms
					LEFT JOIN {$this->db_prefix}tax ON {$this->db_prefix}terms.id = {$this->db_prefix}tax.term
					LEFT JOIN {$wpdb->prefix}posts ON {$this->db_prefix}tax.post_id = {$wpdb->prefix}posts.ID
					{$this->sql_join}
					WHERE {$this->sql_term_where}
					{$this->sql_status}
					AND {$wpdb->prefix}posts.post_type = '{$postType}'
					{$this->sql_exclude}
					{$this->sql_include}
					AND {$this->db_prefix}tax.taxonomy IN ('" . implode( "','", $postTypeTaxonomies ) . "')
					{$this->sql_conditions}
					GROUP BY {$this->db_prefix}tax.post_id
				) taxweights{$i} USING(post_id)";
			$i++;
		}
	}


	/**
	 * Generate the SQL that closes the per-post type sub-query
	 *
	 * @param string $postType The post type
	 * @param bool|int $attribute_to The attribution target post ID (if applicable)
	 *
	 * @since 1.8
	 */
	private function query_post_type_close( $postType, $attribute_to = false ) {
		global $wpdb;
		// cap off each enabled post type subquery
		$this->sql .= "
			WHERE {$this->sql_term_where}
			{$this->sql_status}
			AND {$wpdb->prefix}posts.post_type = '{$postType}'
			{$this->sql_exclude}
			{$this->sql_include}
			{$this->sql_conditions}
			GROUP BY {$wpdb->prefix}posts.ID";

		if( isset( $attribute_to ) && !empty( $attribute_to ) ) {
			// $attributedTo was defined in the initial conditional
			$attributedTo = absint( $attribute_to );
			$this->sql .= ") `attributed{$postType}` ON $attributedTo = {$wpdb->prefix}posts.ID";
		} else {
			$this->sql .= ") AS `{$postType}weights` ON `{$postType}weights`.post_id = {$wpdb->prefix}posts.ID";
		}
	}


	/**
	 * Generate the SQL that limits search results to a specific minimum weight per post type
	 *
	 * @since 1.8
	 */
	private function query_limit_post_type_to_weight() {
		$this->sql .= " WHERE ";

		foreach( $this->engineSettings as $postType => $postTypeWeights ) {
			if( isset( $postTypeWeights['enabled'] ) && $postTypeWeights['enabled'] == true && empty( $postTypeWeights['options']['attribute_to'] ) ) {
				$this->sql .= " COALESCE(`{$postType}weight`,0) +";
			}
		}

		foreach( $this->engineSettings as $postType => $postTypeWeights ) {
			if( isset( $postTypeWeights['enabled'] ) && $postTypeWeights['enabled'] == true && !empty( $postTypeWeights['options']['attribute_to'] ) ) {
				$attributedTo = intval( $postTypeWeights['options']['attribute_to'] );
				// make sure we're not excluding the attributed post id
				if( ! in_array( $attributedTo, $this->excluded ) ) {
					$this->sql .= " COALESCE(`{$postType}attr`,0) +";
				}
			}
		}

		$this->sql = substr( $this->sql, 0, strlen( $this->sql ) - 2 ); // trim off the extra +
		$this->sql .= " > " . absint( apply_filters( 'searchwp_weight_threshold', 0 ) ) . " ";
	}


	/**
	 * Generate the SQL that limits search results to a specific minimum weight overall
	 *
	 * @since 1.8
	 */
	private function query_limit_to_weight() {
		$this->sql .= " WHERE   ";

		foreach( $this->engineSettings as $postType => $postTypeWeights ) {
			if( isset( $postTypeWeights['enabled'] ) && $postTypeWeights['enabled'] == true ) {
				$termCounter = 1;
				if( empty( $postTypeWeights['options']['attribute_to'] ) ) {
					foreach( $this->terms as $term ) {
						$this->sql .= "COALESCE(term{$termCounter}.`{$postType}weight`,0) + ";
						$termCounter++;
					}
				} else {
					foreach( $this->terms as $term ) {
						$this->sql .= "COALESCE(term{$termCounter}.`{$postType}attr`,0) + ";
						$termCounter++;
					}
				}
			}
		}

		$this->sql = substr( $this->sql, 0, strlen( $this->sql ) - 2 ); // trim off the extra +
		$this->sql .= " > " . absint( apply_filters( 'searchwp_weight_threshold', 0 ) ) . " ";
	}


	/**
	 * Dynamically generate SQL query based on engine settings and retrieve a weighted, ordered list of posts
	 *
	 * @return bool|array Post IDs found in the index
	 * @since 1.0
	 */
	function queryForPostIDs() {
		global $wpdb;

		do_action( 'searchwp_log', 'queryForPostIDs()' );

		// check to make sure there are settings for the current engine
		if( !isset( $this->settings['engines'][$this->engine] ) && is_array( $this->settings['engines'][$this->engine] ) ) {
			return false;
		}

		// check to make sure we actually have terms to search
		// TODO: refactor this when this method is refactored for 2.0
		if( empty( $this->terms ) ) {
			// short circuit
			$this->foundPosts = 0;
			$this->maxNumPages = 0;
			$this->postIDs = array();
			return false;
		}

		// pull out our engine-specific settings
		$this->engineSettings = $this->settings['engines'][$this->engine];

		// allow filtration of settings at runtime
		$this->engineSettings = apply_filters( "searchwp_engine_settings_{$this->engine}", $this->engineSettings, $this->terms );

		// check to make sure that all post types in the settings are still in fact registered and active
		// e.g. in case a Custom Post Type was saved in the settings but no longer exists
		$this->validate_post_types();

		// we might need to short circuit for a number of reasons
		if( ! $this->any_enabled_post_types() ) {
			return false;
		}

		// we're going to exclude entered IDs for the query as a whole
		// need to get these IDs early because if an attributed post ID is excluded we need to omit it from
		// the query entirely
		$this->set_excluded_ids_from_settings();

		// pull any excluded IDs based on taxonomy term
		$this->set_excluded_ids_from_taxonomies();

		// perform our AND logic before getting started
		// e.g. we're going to limit to posts that have all of the search terms
		$this->maybe_do_and_logic();

		$this->exclude_posts_by_weight();

		// allow devs to filter excluded IDs
		$this->excluded = apply_filters( 'searchwp_exclude', $this->excluded, $this->engine, $this->terms );
		$this->sql_exclude = ( !empty( $this->excluded ) ) ? " AND {$wpdb->prefix}posts.ID NOT IN (" . implode( ',', $this->excluded ) . ") " : '';

		// if there's an insane number of posts returned, we're dealing with a site with a lot of similar content
		// so we need to trim out the initial results by relevance before proceeding else we'll have a wicked slow query
		$parity = count( $this->terms );
		$maxNumAndResults = absint( apply_filters( 'searchwp_max_and_results', 300 ) );
		if( $parity > 1 && apply_filters( 'searchwp_refine_and_results', true ) && count( $this->relevant_post_ids) > $maxNumAndResults ) {
			$this->relevant_post_ids = $this->get_post_ids_via_and_in_title();
		}

		// make sure we've got an array of unique integers
		$this->relevant_post_ids = array_map( 'absint', array_unique( $this->relevant_post_ids ) );

		// allow devs to filter included post IDs
		add_filter( 'searchwp_force_wp_query', '__return_true' );
		$this->included = apply_filters( 'searchwp_include', $this->relevant_post_ids, $this->engine, $this->terms );
		remove_filter( 'searchwp_force_wp_query', '__return_true' );

		// allow devs to force AND logic all the time, no matter what (if there was more than one search term)
		$forceAnd = ( count( $this->terms ) > 1 && apply_filters( 'searchwp_and_logic_only', false ) ) ? true : false;

		// if it was totally empty and AND logic is forced, we'll hit a SQL error, so populate it with an impossible ID
		if( empty( $this->included ) && $forceAnd ) {
			$this->included = array( 0 );
		}

		$this->sql_include = ( ( is_array( $this->included ) && !empty( $this->included ) ) || $forceAnd ) ? " AND {$wpdb->prefix}posts.ID IN (" . implode( ',', $this->included ) . ") " : '';

		/**
		 * Build the search query
		 */
		$this->query_open();
		$this->query_sum_post_type_weights();
		$this->query_sum_final_weight();

		// allow for pre-algorithm join
		$this->sql = ' ' . (string) apply_filters( 'searchwp_query_main_join', $this->sql, $this->engine ) . ' ';

		// loop through each submitted term
		$termCounter = 1;
		foreach( $this->terms as $term ) {
			$this->query_open_term();

			// build our post type queries
			foreach( $this->engineSettings as $postType => $postTypeWeights ) {
				if( isset( $postTypeWeights['enabled'] ) && $postTypeWeights['enabled'] == true ) {
					// TODO: store our post format clause and integrate
					// TODO: store our post status clause and integrate

					// if it's an attachment we need to force 'inherit'
					$post_statuses = $postType == 'attachment' ? array( 'inherit' ) : $this->post_statuses;
					$this->sql_status = "AND {$wpdb->prefix}posts.post_status IN ( '" . implode( "', '", $post_statuses ) . "' ) ";

					// determine whether we need to limit to a mime type
					if( isset( $postTypeWeights['options']['mimes'] ) && !empty( $postTypeWeights['options']['mimes'] ) ) {
						$mimes = explode( ',', $postTypeWeights['options']['mimes'] );
						$this->query_limit_by_mimes( $mimes );
					}

					// prep the term
					$prepared_term = strtolower( $wpdb->prepare( '%s', $term ) );
					$term = substr( $prepared_term, 1, strlen( $prepared_term ) - 2 );
					$original_prepped_term = $term;

					// determine whether we're stemming or not
					$term_or_stem = 'term';
					if( isset( $postTypeWeights['options']['stem'] ) && ! empty( $postTypeWeights['options']['stem'] ) ) {
						// build our stem
						$term_or_stem = 'stem';
						$unstemmed = $term;
						$maybeStemmed = apply_filters( 'searchwp_custom_stemmer', $unstemmed );

						// if the term was stemmed via the filter use it, else generate our own
						$term = ( $unstemmed == $maybeStemmed ) ? $this->stemmer->stem( $term ) : $maybeStemmed;
					}
					// set up our term operator (e.g. LIKE terms or fuzzy matching)

					// since we're going to allow extending the term WHERE SQL, we need to force $term as an array
					// because in many cases with extensions it will be
					$term = array( $term );

					// let extensions filter this all day
					$term = apply_filters( 'searchwp_term_in', $term, $this->engine );

					// prepare our terms
					if( ! is_array( $term ) || empty( $term ) ) {
						// if it got messed with so bad it's no longer an array, we're going to revert
						$term = array( $prepared_term );
					}

					$term = array_unique( $term );

					// hopefully the developer sanitized their terms, but they might have prepared them (i.e. they're wrapped in single quotes)
					foreach( $term as $raw_term_key => $raw_term ) {
						if( "'" == substr( $raw_term, 0, 1 ) && "'" == substr( $raw_term, strlen( $raw_term ) - 1 ) ) {
							$raw_term = substr( $raw_term, 1, strlen( $raw_term ) - 2 );
						}
						$raw_term = trim( sanitize_text_field( $raw_term ) );
						$term[$raw_term_key] = strtolower( $wpdb->prepare( '%s', $raw_term ) );
					}

					// finalize our term WHERE
					$this->sql_term_where = " {$this->db_prefix}terms." . $term_or_stem . " IN (" . implode( ',', $term ) . ")";

					// reset back to our original term
					$term = $original_prepped_term;

					// we need to use absint because if a weight was set to -1 for exclusion, it was already forcefully excluded
					$titleWeight    = isset( $postTypeWeights['weights']['title'] )   ? absint( $postTypeWeights['weights']['title'] )   : 0;
					$slugWeight     = isset( $postTypeWeights['weights']['slug'] )    ? absint( $postTypeWeights['weights']['slug'] )    : 0;
					$contentWeight  = isset( $postTypeWeights['weights']['content'] ) ? absint( $postTypeWeights['weights']['content'] ) : 0;
					$excerptWeight  = isset( $postTypeWeights['weights']['excerpt'] ) ? absint( $postTypeWeights['weights']['excerpt'] ) : 0;

					if( apply_filters( 'searchwp_index_comments', true ) ) {
						$commentWeight = isset( $postTypeWeights['weights']['comment'] ) ? absint( $postTypeWeights['weights']['comment'] ) : 0;
					} else {
						$commentWeight = 0;
					}

					// build the SQL to accommodate Custom Fields
					$custom_field_weights = isset( $postTypeWeights['weights']['cf'] ) ? $postTypeWeights['weights']['cf'] : 0;
					$coalesceCustomFields = $this->query_coalesce_custom_fields( $custom_field_weights );

					// build the SQL to accommodate Taxonomies
					$taxonomy_weights = isset( $postTypeWeights['weights']['tax'] ) ? $postTypeWeights['weights']['tax'] : 0;
					$coalesceTaxonomies = $this->query_coalesce_taxonomies( $taxonomy_weights );

					// allow additional tables to be joined
					$this->sql_join = apply_filters( 'searchwp_query_join', '', $postType, $this->engine );
					if( !is_string( $this->sql_join ) ) {
						$this->sql_join = '';
					}

					// allow additional conditions
					$this->sql_conditions = apply_filters( 'searchwp_query_conditions', '', $postType, $this->engine );
					if( !is_string( $this->sql_conditions ) ) {
						$this->sql_conditions = '';
					}

					// if we're dealing with attributed weight we need to make sure that the attribution target was not excluded
					$excludedByAttribution = false;
					$attributedTo = false;
					if( isset( $postTypeWeights['options']['attribute_to'] ) && !empty( $postTypeWeights['options']['attribute_to'] ) ) {
						$postColumn = 'ID';
						$attributedTo = intval( $postTypeWeights['options']['attribute_to'] );
						if( in_array( $attributedTo, $this->excluded ) ) {
							$excludedByAttribution = true;
						}
					} else {
						// if it's an attachment and we want to attribute to the parent, we need to set that here
						$postColumn = isset( $postTypeWeights['options']['parent'] ) ? 'post_parent' : 'ID';
					}

					// open up the post type subquery if not excluded by attribution
					if( !$excludedByAttribution ) {
						$post_type_params = array(
							'post_type'         => $postType,
							'post_column'       => $postColumn,
							'title_weight'      => $titleWeight,
							'slug_weight'       => $slugWeight,
							'content_weight'    => $contentWeight,
							'comment_weight'    => $commentWeight,
							'excerpt_weight'    => $excerptWeight,
							'custom_fields'     => isset( $coalesceCustomFields ) ? $coalesceCustomFields : '',
							'taxonomies'        => isset( $coalesceTaxonomies ) ? $coalesceTaxonomies : '',
							'attributed_to'     => $attributedTo,
						);
						$this->query_post_type_open( $post_type_params );

						// handle custom field weights
						if( isset( $postTypeWeights['weights']['cf'] ) && is_array( $postTypeWeights['weights']['cf'] ) && !empty( $postTypeWeights['weights']['cf'] ) ) {
							$this->query_post_type_custom_field_weights( $postType, $postTypeWeights['weights']['cf'] );
						}

						// handle taxonomy weights
						if( isset( $postTypeWeights['weights']['tax'] ) && is_array( $postTypeWeights['weights']['tax'] ) && !empty( $postTypeWeights['weights']['tax'] ) ) {
							$this->query_post_type_taxonomy_weights( $postType, $postTypeWeights['weights']['tax'] );
						}

						// close out the per-post type sub-query
						$attribute_to = isset( $postTypeWeights['options']['attribute_to'] ) ? $postTypeWeights['options']['attribute_to'] : false;
						$this->query_post_type_close( $postType, $attribute_to );
					}

				}
			}

			// make sure we're only getting posts with actual weight
			$this->query_limit_post_type_to_weight();

			$this->sql .= $this->postStatusLimiterSQL( $this->engineSettings );

			$this->sql .= " GROUP BY post_id";

			$this->sql .= " ) AS term{$termCounter} ON term{$termCounter}.post_id = {$wpdb->prefix}posts.ID ";

			$termCounter++;
		}

		/**
		 * END LOOP THROUGH EACH SUBMITTED TERM
		 */


		// make sure we're only getting posts with actual weight
		$this->query_limit_to_weight();

		$this->sql .= $this->postStatusLimiterSQL( $this->engineSettings );

		$modifier = ( $this->postsPer < 1 ) ? 1 : $this->postsPer; // if posts_per_page is -1 there's no offset
		$start = intval( ( $this->page - 1 ) * $modifier );
		$total = intval( $this->postsPer );
		$order = $this->order;

		// accommodate a custom offset
		$start = absint( apply_filters( 'searchwp_query_limit_start', $start, $this->page, $this->engine ) );
		$total = absint( apply_filters( 'searchwp_query_limit_total', $total, $this->page, $this->engine ) );

		$extraWhere = apply_filters( 'searchwp_where', '', $this->engine );
		$this->sql .= " " . $extraWhere . " ";

		// allow developers to order by date
		$orderByDate = apply_filters( 'searchwp_return_orderby_date', false, $this->engine );
		$finalOrderBySQL = $orderByDate ? " ORDER BY post_date {$order}, finalweight {$order} " : " ORDER BY finalweight {$order}, post_date DESC ";

		// allow developers to return completely random results that meet the minumum weight
		if( apply_filters( 'searchwp_return_orderby_random', false, $this->engine ) ) {
			$finalOrderBySQL = " ORDER BY RAND() ";
		}

		// allow for arbitrary ORDER BY filtration
		$finalOrderBySQL = apply_filters( 'searchwp_query_orderby', $finalOrderBySQL );

		$this->sql .= "
			GROUP BY {$wpdb->prefix}posts.ID
			{$finalOrderBySQL}
		";

		if( $this->postsPer > 0 ) {
			$this->sql .= "LIMIT {$start}, {$total}";
		}

		$this->sql = str_replace( "\n", " ", $this->sql );
		$this->sql = str_replace( "\t", " ", $this->sql );

		// allow BIG_SELECTS
		$bigSelects = apply_filters( 'searchwp_big_selects', false );
		if( $bigSelects ) {
			$wpdb->query( 'SET SQL_BIG_SELECTS=1' );
		}

		$postIDs = $wpdb->get_col( $this->sql );

		do_action( 'searchwp_log', 'Search results: ' . var_export( $postIDs, true ) );

		// retrieve how many total posts were found without the limit
		$this->foundPosts = (int) $wpdb->get_var(
			apply_filters_ref_array(
				'found_posts_query',
				array( 'SELECT FOUND_ROWS()', &$wpdb )
			)
		);

		// store an accurate max_num_pages for $wp_query
		$this->maxNumPages = ( $this->postsPer < 1 ) ? 1 : ceil( $this->foundPosts / $this->postsPer );

		// store our post IDs
		$this->postIDs = $postIDs;

		return true;
	}


	/**
	 * Generate the SQL used to limit the results pool as much as possible while considering enabled post types
	 *
	 * @param $engineSettings array The engine settings from the SearchWP settings
	 *
	 * @return string
	 */
	private function postStatusLimiterSQL( $engineSettings ) {
		global $wpdb;

		$prefix = $wpdb->prefix;
		$sql    = '';

		// add more limiting
		$finalPostTypes = array();
		$finalPostTypesIncludesAttachments = false;
		foreach( $engineSettings as $postType => $postTypeWeights ) {
			if( isset( $postTypeWeights['enabled'] ) && $postTypeWeights['enabled'] == true ) {
				if( $postType == 'attachment' ) {
					$finalPostTypesIncludesAttachments = true;
				} else {
					$finalPostTypes[] = $postType;
				}
			}
		}

		$sql .= " AND ( ";

		// based on whether attachments are the ONLY enabled post type, we'll build out this statement
		if( ! empty( $finalPostTypes ) ) {
			$sql .= " ( {$prefix}posts.post_status  IN ( '" . implode( "', '", $this->post_statuses ) . "' )  AND {$prefix}posts.post_type IN ('" . implode( "','", $finalPostTypes ) . "') ) ";

			// this OR should be put in place only if there are other enabled post types, else the limiter will get picked up 6 lines down
			if( $finalPostTypesIncludesAttachments ) {
				$sql .= ' OR ';
			}
		}

		if( $finalPostTypesIncludesAttachments ) {
			$sql .= "{$prefix}posts.post_type = 'attachment' ";
		}

		$sql .= " ) ";

		return $sql;
	}


	/**
	 * Returns the maximum number of pages of results
	 *
	 * @return int The total number of pages
	 * @since 1.0.5
	 */
	function getMaxNumPages() {
		return $this->maxNumPages;
	}


	/**
	 * Returns the number of found posts
	 *
	 * @return int The total number of posts
	 * @since 1.0.5
	 */
	function getFoundPosts() {
		return $this->foundPosts;
	}


	/**
	 * Returns the number of the current page of results
	 *
	 * @return int The current page
	 * @since 1.0.5
	 */
	function getPage() {
		return $this->page;
	}

}
