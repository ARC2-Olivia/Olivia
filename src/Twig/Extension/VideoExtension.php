<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\VideoRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class VideoExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('is_topic_index_video_url_available', [VideoRuntime::class, 'isTopicIndexVideoUrlAvailable']),
            new TwigFunction('get_topic_index_video_url', [VideoRuntime::class, 'getTopicIndexVideoUrl'])
        ];
    }
}