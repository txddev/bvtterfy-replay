<?php

namespace Bvtterfly\Replay;

use Illuminate\Http\Request;

class RequestHelper
{
    public static function signature(Request $request): string
    {
        $hashAlgo = 'md5';

        if (in_array('xxh3', hash_algos())) {
            $hashAlgo = 'xxh3';
        }
        
        $data = [
            $request->ip(),
            $request->path(),
            $request->all(),
        ];
        if(config('replay.include_headers_in_signature')){
            $data[] = $request->headers->all();
        }
        if(config('replay.include_user_in_signature')){
            $data[] = $request->user();
        }
        

        return hash($hashAlgo, json_encode($data));
    }
}
