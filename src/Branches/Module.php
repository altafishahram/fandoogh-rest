<?php
namespace FandooghRest\Branches;

defined('ABSPATH') || exit;

final class Module
{
    private array $contexts = [];
    public function register(): void
    {
        add_filter('woocommerce_product_data_store_cpt_get_products_query', [$this, 'productsQuery'], 10, 2);
        add_filter('rest_request_before_callbacks', [$this, 'before'], -100, 3);
        add_filter('rest_request_after_callbacks', [$this, 'after'], 1000, 3);
    }
    public function productsQuery(array $query, array $vars): array
    {
        if (!isset($vars['fandoogh_branch_id'])) { return $query; }
        $id = (int) $vars['fandoogh_branch_id'];
        $ownership = ['key' => Branches::META, 'value' => $id, 'compare' => '=', 'type' => 'NUMERIC'];
        if ($id === Branches::defaultId()) {
            $ownership = ['relation' => 'OR', $ownership,
                ['key' => Branches::META, 'compare' => 'NOT EXISTS'],
                ['key' => Branches::META, 'value' => '', 'compare' => '='],
                ['key' => Branches::META, 'value' => '0', 'compare' => '=']];
        }
        $query['meta_query'][] = $ownership;
        return $query;
    }
    public function before($response, $handler, $request): mixed
    {
        if (!str_starts_with($request->get_route(), '/admincafe/v1/')) { return $response; }
        $this->contexts[spl_object_id($request)][] = Branches::current();
        if (is_wp_error($response)) { return $response; }
        $query = $request->get_query_params();
        $body = $request->get_json_params();
        $header = $request->get_header('X-Fandoogh-Branch');
        $explicit = array_key_exists('branch_id', $query) || $header !== null || (is_array($body) && array_key_exists('branch_id', $body));
        $raw = $query['branch_id'] ?? $header ?? (is_array($body) ? ($body['branch_id'] ?? null) : null);
        if ($explicit && ((!is_int($raw) && !is_string($raw)) || !preg_match('/^[1-9][0-9]*$/D', (string) $raw) || strlen((string) $raw) > 10)) {
            return new \WP_Error('fandoogh_branch', __('Invalid branch.', 'fandoogh-rest'), ['status' => 400]);
        }
        foreach ([$query['branch_id'] ?? null, $header, is_array($body) ? ($body['branch_id'] ?? null) : null] as $selection) {
            if ($selection !== null && ((!is_int($selection) && !is_string($selection)) || !preg_match('/^[1-9][0-9]*$/D', (string) $selection) || (int) $selection !== (int) $raw)) {
                return new \WP_Error('fandoogh_branch', __('Conflicting branch selection.', 'fandoogh-rest'), ['status' => 400]);
            }
        }
        $id = $explicit ? (int) $raw : Branches::defaultId();
        $management = str_starts_with($request->get_route(), '/admincafe/v1/manage/');
        if (!$management && ($request->get_param('table') !== null || $request->get_param('table_token') !== null)) {
            $token = $request->get_param('table') ?? $request->get_param('table_token');
            if (!is_string($token)) { return new \WP_Error('fandoogh_branch', __('Invalid table.', 'fandoogh-rest'), ['status' => 400]); }
            $table = \FandooghRest\Tables\Tables::findByToken($token);
            if ($table) {
                if ($explicit && (int) $table['branch_id'] !== $id) { return new \WP_Error('fandoogh_branch', __('Conflicting branch selection.', 'fandoogh-rest'), ['status' => 400]); }
                $id = (int) $table['branch_id'];
            }
        }
        if (!$explicit && $request->get_route() === '/admincafe/v1/manage/bootstrap') { $id = Branches::allowedIds()[0] ?? 1; }
        $branch = Branches::get($id);
        $branchControl = Branches::isCentral() && preg_match('#^/admincafe/v1/manage/branches(?:/[0-9]+)?$#D', $request->get_route());
        if (!$branchControl && (!$branch || (!$management && empty($branch['enabled']) && $request->get_route() !== '/admincafe/v1/orders/track'))) { return new \WP_Error('fandoogh_branch', __('Branch is unavailable.', 'fandoogh-rest'), ['status' => 404]); }
        if ($management && !$branchControl && !Branches::canAccess($id)) { return new \WP_Error('rest_forbidden', __('You do not have access to this branch.', 'fandoogh-rest'), ['status' => 403]); }
        Branches::setCurrent($id);
        return $response;
    }
    public function after($response, $handler, $request): mixed
    {
        $key = spl_object_id($request);
        if (!empty($this->contexts[$key])) { Branches::setCurrent(array_pop($this->contexts[$key])); }
        if (empty($this->contexts[$key])) { unset($this->contexts[$key]); }
        return $response;
    }
}
