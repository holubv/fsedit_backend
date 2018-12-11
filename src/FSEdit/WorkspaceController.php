<?php

namespace FSEdit;

use Slim\Http\Request;
use Slim\Http\Response;

class WorkspaceController extends Controller
{
    /**
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     * @throws \Exception
     */
    public function structure(Request $req, Response $res, $args)
    {
        $wHash = $args['workspace'];
        if (!$wHash) {
            throw new \Exception('workspace hash is missing');
        }

        $workspace = Workspace::getByHash($this->database, $wHash);
        $workspace->canReadEx();

        $structure = $workspace->getStructure();

        if (count($structure) <= 1) {
            return $this->json($res, []);
        }

        self::cleanStructure($structure);
        $st = self::buildTree($structure);
        $st = self::nestedValues($st);
        //todo cleanup

        $st = $st[0];
        if (isset($st['children'])) {
            $st = $st['children'];
        } else {
            $st = [];
        }

        return $this->json($res, $st);
    }

    private static function cleanStructure(&$elements)
    {
        foreach ($elements as &$element) {
            unset($element['lft']);
            unset($element['rgt']);
            unset($element['workspace_id']);
        }
    }

    /**
     * @param array $elements
     * @param int $parentId
     * @return array
     */
    private static function buildTree(&$elements, $parentId = 0)
    {
        $branch = array();

        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = self::buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[$element['id']] = $element;
                unset($elements[$element['id']]);
            }
        }
        return $branch;
    }

    private static function nestedValues($arr)
    {
        usort($arr, function ($a, $b) {
            if (isset($a['children']) && !isset($b['children'])) {
                return -1;
            }
            if (!isset($a['children']) && isset($b['children'])) {
                return 1;
            }
            return strcmp($a['name'], $b['name']);
        });
        foreach ($arr as &$element) {
            if (isset($element['children'])) {
                $element['children'] = self::nestedValues($element['children']);
            } else {
                $element['droppable'] = false;
            }
        }
        return $arr;
    }
}