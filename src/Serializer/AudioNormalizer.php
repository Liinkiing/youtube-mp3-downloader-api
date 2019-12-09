<?php


namespace App\Serializer;


use App\Entity\Audio;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class AudioNormalizer implements ContextAwareNormalizerInterface
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
        return $data instanceof Audio;
    }

    public function normalize($audio, string $format = null, array $context = [])
    {
        /** @var Audio $audio */
        $data = $this->normalizer->normalize($audio, $format, $context);

        $groups =
            isset($context['groups']) && \is_array($context['groups']) ? $context['groups'] : [];

        if (\in_array('api', $groups, true)) {
            $data['_href'] = [
                'download' => $this->router->generate('api.audio.download', [
                    'id' => $audio->getId()
                ], RouterInterface::ABSOLUTE_URL)
            ];
        }

        return $data;
    }
}
