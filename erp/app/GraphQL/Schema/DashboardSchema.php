<?php

namespace App\GraphQL\Schema;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * GraphQL Schema Definitions for Dashboard Types.
 */
class DashboardSchema
{
    private static array $types = [];

    public static function salesType(): ObjectType
    {
        return self::$types['sales'] ??= new ObjectType([
            'name' => 'Sales',
            'description' => 'Daily sales aggregation by route',
            'fields' => [
                'date' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'Date of the sales aggregation',
                ],
                'routeId' => [
                    'type' => Type::nonNull(Type::int()),
                    'description' => 'Route identifier',
                ],
                'totalHt' => [
                    'type' => Type::nonNull(Type::float()),
                    'description' => 'Total amount without taxes',
                ],
                'totalTtc' => [
                    'type' => Type::nonNull(Type::float()),
                    'description' => 'Total amount with taxes',
                ],
                'nbOrders' => [
                    'type' => Type::nonNull(Type::int()),
                    'description' => 'Number of orders',
                ],
                'nbClientsVisited' => [
                    'type' => Type::int(),
                    'description' => 'Number of clients visited',
                ],
                'updatedAt' => [
                    'type' => Type::string(),
                    'description' => 'Last update timestamp',
                ],
            ],
        ]);
    }

    public static function topArticleType(): ObjectType
    {
        return self::$types['topArticle'] ??= new ObjectType([
            'name' => 'TopArticle',
            'description' => 'Top selling article statistics',
            'fields' => [
                'articleId' => [
                    'type' => Type::nonNull(Type::int()),
                ],
                'quantitySold' => [
                    'type' => Type::nonNull(Type::float()),
                ],
                'amountHt' => [
                    'type' => Type::nonNull(Type::float()),
                ],
                'article' => [
                    'type' => self::articleBriefType(),
                    'description' => 'Article reference information',
                ],
            ],
        ]);
    }

    public static function articleBriefType(): ObjectType
    {
        return self::$types['articleBrief'] ??= new ObjectType([
            'name' => 'ArticleBrief',
            'fields' => [
                'id' => ['type' => Type::int()],
                'name' => ['type' => Type::string()],
                'reference' => ['type' => Type::string()],
            ],
        ]);
    }

    public static function kpiType(): ObjectType
    {
        return self::$types['kpi'] ??= new ObjectType([
            'name' => 'SalesKPI',
            'description' => 'Key Performance Indicators for sales',
            'fields' => [
                'revenue' => [
                    'type' => Type::nonNull(self::revenueKpiType()),
                ],
                'orders' => [
                    'type' => Type::nonNull(self::ordersKpiType()),
                ],
                'visits' => [
                    'type' => Type::nonNull(self::visitsKpiType()),
                ],
                'routes' => [
                    'type' => Type::nonNull(self::routesKpiType()),
                ],
            ],
        ]);
    }

    public static function revenueKpiType(): ObjectType
    {
        return self::$types['revenueKpi'] ??= new ObjectType([
            'name' => 'RevenueKPI',
            'fields' => [
                'ht' => ['type' => Type::nonNull(Type::float())],
                'ttc' => ['type' => Type::nonNull(Type::float())],
                'growthPercent' => ['type' => Type::float()],
            ],
        ]);
    }

    public static function ordersKpiType(): ObjectType
    {
        return self::$types['ordersKpi'] ??= new ObjectType([
            'name' => 'OrdersKPI',
            'fields' => [
                'total' => ['type' => Type::nonNull(Type::int())],
                'avgPerRoute' => ['type' => Type::float()],
            ],
        ]);
    }

    public static function visitsKpiType(): ObjectType
    {
        return self::$types['visitsKpi'] ??= new ObjectType([
            'name' => 'VisitsKPI',
            'fields' => [
                'total' => ['type' => Type::nonNull(Type::int())],
                'conversionRate' => ['type' => Type::float()],
            ],
        ]);
    }

    public static function routesKpiType(): ObjectType
    {
        return self::$types['routesKpi'] ??= new ObjectType([
            'name' => 'RoutesKPI',
            'fields' => [
                'active' => ['type' => Type::nonNull(Type::int())],
                'avgRevenuePerRoute' => ['type' => Type::float()],
            ],
        ]);
    }

    public static function connectionType(ObjectType $nodeType): ObjectType
    {
        $name = $nodeType->name . 'Connection';
        return self::$types[$name] ??= new ObjectType([
            'name' => $name,
            'fields' => [
                'edges' => [
                    'type' => Type::listOf(self::edgeType($nodeType)),
                ],
                'pageInfo' => [
                    'type' => Type::nonNull(self::pageInfoType()),
                ],
            ],
        ]);
    }

    public static function edgeType(ObjectType $nodeType): ObjectType
    {
        $name = $nodeType->name . 'Edge';
        return self::$types[$name] ??= new ObjectType([
            'name' => $name,
            'fields' => [
                'node' => ['type' => Type::nonNull($nodeType)],
                'cursor' => ['type' => Type::nonNull(Type::string())],
            ],
        ]);
    }

    public static function pageInfoType(): ObjectType
    {
        return self::$types['pageInfo'] ??= new ObjectType([
            'name' => 'PageInfo',
            'fields' => [
                'hasNextPage' => ['type' => Type::nonNull(Type::boolean())],
                'endCursor' => ['type' => Type::string()],
            ],
        ]);
    }

    public static function liveSnapshotType(): ObjectType
    {
        return self::$types['liveSnapshot'] ??= new ObjectType([
            'name' => 'LiveSnapshot',
            'description' => 'Real-time dashboard snapshot',
            'fields' => [
                'generatedAt' => ['type' => Type::nonNull(Type::string())],
                'today' => [
                    'type' => Type::nonNull(self::todayStatsType()),
                ],
                'topArticles' => [
                    'type' => Type::listOf(self::topArticleBriefType()),
                ],
            ],
        ]);
    }

    public static function todayStatsType(): ObjectType
    {
        return self::$types['todayStats'] ??= new ObjectType([
            'name' => 'TodayStats',
            'fields' => [
                'revenueHt' => ['type' => Type::nonNull(Type::float())],
                'ordersCount' => ['type' => Type::nonNull(Type::int())],
                'visitsCount' => ['type' => Type::nonNull(Type::int())],
            ],
        ]);
    }

    public static function topArticleBriefType(): ObjectType
    {
        return self::$types['topArticleBrief'] ??= new ObjectType([
            'name' => 'TopArticleBrief',
            'fields' => [
                'articleId' => ['type' => Type::nonNull(Type::int())],
                'quantity' => ['type' => Type::nonNull(Type::float())],
                'amount' => ['type' => Type::nonNull(Type::float())],
            ],
        ]);
    }
}
