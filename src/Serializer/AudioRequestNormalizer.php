<?php


namespace App\Serializer;


use App\Entity\AudioRequest;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class AudioRequestNormalizer implements ContextAwareNormalizerInterface
{

    private $router;
    private $normalizer;

    public function __construct(UrlGeneratorInterface $router, ObjectNormalizer $normalizer)
    {
        $this->router = $router;
        $this->normalizer = $normalizer;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof AudioRequest;
    }

    public function normalize($audioRequest, string $format = null, array $context = [])
    {
        /** @var AudioRequest $audioRequest */
        $data = $this->normalizer->normalize($audioRequest, $format, $context);

        $groups =
            isset($context['groups']) && \is_array($context['groups']) ? $context['groups'] : [];

        if (\in_array('api', $groups, true)) {
            $data['_href'] = [
                'self' => $this->router->generate('api.audio_request.show', [
                    'id' => $audioRequest->getId()
                ], RouterInterface::ABSOLUTE_URL)
            ];
        }

        return $data;
    }
}
