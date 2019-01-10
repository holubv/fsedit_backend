<?php

namespace FSEdit;

use FSEdit\Exception\BadRequestException;
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
    public function create(Request $req, Response $res, $args)
    {
        $userId = $this->getUserId();
        $workspace = $this->Workspace()->create($userId);

        return $this->json($res, [
            'hash' => $workspace->getHash(),
            'editToken' => $workspace->getEditToken()
        ]);
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     * @throws \Exception
     */
    public function listWorkspaces(Request $req, Response $res)
    {
        $user = $this->requireUser();

        $data = $this->database->query(
            ' select workspaces.id, hash, created, count(*) as count, GROUP_CONCAT(name SEPARATOR \', \') as files
              from workspaces
              join file_tree on workspaces.id = file_tree.workspace_id
              where user_id = :user_id and file_tree.name is not null and file_tree.lft > 1
              group by workspaces.id
              order by id desc
              limit 10;',
            [':user_id' => $user->getId()]
        )->fetchAll(\PDO::FETCH_ASSOC);

        return $this->json($res, $data);
    }

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
            throw new BadRequestException('workspace hash is missing');
        }

        $workspace = $this->Workspace()->loadByHash($wHash);
        $workspace->canReadEx();

        $isOwner = $workspace->isOwner($this->user);
        $structure = $workspace->getStructure();

        if (count($structure) <= 1) {
            return $this->json($res, [
                'owner' => $isOwner,
                'structure' => []
            ]);
        }

        self::cleanStructure($structure);
        self::generatePaths($structure);

        $st = self::buildTree($structure);
        $st = self::nestedValues($st);
        //todo cleanup

        $st = $st[0];
        if (isset($st['children'])) {
            $st = $st['children'];
        } else {
            $st = [];
        }

        return $this->json($res, [
            'owner' => $isOwner,
            'structure' => $st
        ]);
    }

    private static function cleanStructure(&$elements)
    {
        foreach ($elements as &$element) {
            $element['id'] = (int)$element['id'];
            $element['parent_id'] = (int)$element['parent_id'];
            $element['level'] = (int)$element['level'];
            unset($element['lft']);
            unset($element['rgt']);
            unset($element['workspace_id']);
        }
    }

    private static function generatePaths(&$elements)
    {
        foreach ($elements as &$element) {
            if ($element['level'] === 0) {
                $element['path'] = null;
                continue;
            }
            if ($element['level'] === 1) {
                $element['path'] = [$element['name']];
                continue;
            }
            $path = [];
            $parent = $element['id'];
            while ($parent) {
                $found = false;
                foreach ($elements as $e) {
                    if ($e['id'] === $parent) {
                        if ($e['name']) {
                            $path[] = $e['name'];
                        }
                        $parent = $e['parent_id'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    break;
                }
            }
            $element['path'] = array_reverse($path);
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
            if (isset($a['file']) && !isset($b['file'])) {
                return 1;
            }
            if (!isset($a['file']) && isset($b['file'])) {
                return -1;
            }
            return strcmp(mb_strtolower($a['name']), mb_strtolower($b['name']));
        });
        foreach ($arr as &$element) {
            if (isset($element['children'])) {
                $element['children'] = self::nestedValues($element['children']);
            }
            if (isset($element['file'])) {
                $element['droppable'] = false;
            }
        }
        return $arr;
    }
}