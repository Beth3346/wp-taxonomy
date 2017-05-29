<?php
namespace WpTaxonomy;

class Taxonomy
{
    public function setTermArgs($term)
    {
        return [
            'post_type' => 'any',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => 'category',
                    'terms' => $term->slug,
                    'field' => 'slug',
                    'operator' => 'IN',
                ]
            ]
        ];
    }

    public function createTermLink($term)
    {
        $term_link = get_term_link($term);

        return '<a href="' . $term_link . '">' . $term->name . '</a>';
    }

    public function createTerm($term)
    {
        $args = $this->setTermArgs($term);

        $query = new \WP_Query($args);
        $post_count = $query->post_count;

        $string = $this->createTermLink($term);

        if (isset($cat_args['count'])) {
            $string .= ' ' . $post_count;
        }

        return $string;
    }

    public function createCategoryList(array $cat_args)
    {
        $string = '<ul>';

        $terms = get_terms('category', $cat_args);

        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $string .= '<li>' . $this->createTerm($term) . '</li>';
            }
        }

        $string .= '</ul>';

        return $string;
    }

    public function createCatArgs(array $attrs)
    {
        $cat_args = [
            'orderby' => 'name',
            'hierarchical' => $attrs['hierarchical'],
            'hide_empty' => 1
        ];

        if ($attrs['by_count']) {
            $cat_args['orderby'] = 'count';
            $cat_args['order'] = 'DESC';
        }

        if ($attrs['num'] != 'all') {
            $cat_args['number'] = $attrs['num'];
        }

        return $cat_args;
    }

    public function getCurrentTax($query)
    {
        if (is_tax()) {
            $tax_term = $query->queried_object;
            return $tax_term->name;
        } else {
            return null;
        }
    }

    public function getRelatedTerms($taxonomy, $type, $terms, $term_tax)
    {
        $rel_terms = [];
        $query = new \WP_Query([
            'post_type' => $type,
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => $term_tax,
                    'terms'    => $terms,
                    'field'    => 'slug',
                ],
            ],
        ]);

        $items = $query->get_posts();

        foreach ($items as $item) {
            $term = wp_get_post_terms($item->ID, $taxonomy);
            array_push($rel_terms, $term[0]->name);
        }

        return array_unique($rel_terms);
    }

    public function isParentTerm($term)
    {
        if ($term->parent == 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getParents($taxonomy)
    {
        $terms = get_terms($taxonomy, 'orderby=count&hide_empty=1&hierarchical=1');
        $parents = [];

        foreach ($terms as $term) {
            if ($this->isParentTerm($term)) {
                array_push($parents, $term);
            }
        }

        return $parents;
    }

    public function termHasPosts($id, $taxonomy)
    {
        $args = [
            'status' => 'publish',
            'tax_query' => [
                [
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => $id
                ]
            ]
        ];

        $term_query =  new \WP_Query($args);
        $term_posts_count = $term_query->found_posts;

        if ($term_posts_count > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getChildren($term, $taxonomy)
    {
        if ($this->isParentTerm($term)) {
            $terms = [];
            $ids = get_term_children($term->term_id, $taxonomy);

            foreach ($ids as $id) {
                if ($this->termHasPosts($id, $taxonomy)) {
                    array_push($terms, get_term($id));
                }
            }
        } else {
            $terms = null;
        }

        return $terms;
    }
}
