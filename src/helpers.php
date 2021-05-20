<?php

function paginate($model, $recordPerPage, $currenPage, $request)
{        
    $count = $model->count();
    
    $perPage = $recordPerPage;
    $page = ($currenPage == 0 ? 1 : $currenPage);
    $offset = ($page - 1) * $perPage;
    $lastPage = ceil($count / $perPage);
    $prev = ($page != $offset + 1) ? $page - 1 : null;
    $next = ($page != $lastPage) ? $page + 1 : null;
    $lastRecordPerPage = ($page != $lastPage) ? ($page * $perPage) : ($count - $offset) + $offset;

    $items = $model->skip($offset)
                ->take($perPage)
                ->get();

    $link = getUrlWithQueryStr($request);

    return [
        'items' => $items,
        'pager' => [
            'total' => $count,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
            'from' => $offset + 1,
            'to' => $lastRecordPerPage,
            'path'  => strrpos($link, '&') ? substr($link, 0, strrpos($link, '&')) : substr($link, 0, strrpos($link, '?')),
            'first_page_url' => $link. 'page=1',
            'prev_page_url' => (!$prev) ? $prev : $link. 'page=' .$prev,
            'next_page_url' => (!$next) ? $next : $link. 'page=' .$next,
            'last_page_url' => $link. 'page=' .$lastPage
        ]
    ];
}

function getUrlWithQueryStr($request)
{
    if($request->getServerParam('QUERY_STRING') === "") { // if querystring in empty
        $qs = '?';
    } else {
        // if found "page=" phrase have to slice out or if not found append querystring with '&'
        if(strrpos($request->getServerParam('QUERY_STRING'), 'page=') === false) {
            $qs = '?'.$request->getServerParam('QUERY_STRING').'&';
        } else {
            if(strrpos($request->getServerParam('QUERY_STRING'), 'page=') > 0) {
                $qs = '?'.substr($request->getServerParam('QUERY_STRING'), 0, 
                        strrpos($request->getServerParam('QUERY_STRING'), 'page='));
            } else {
                $qs = '?';
            }
        }
    }

    return 'http://'.$request->getServerParam('HTTP_HOST'). $request->getServerParam('REDIRECT_URL').$qs;
}
