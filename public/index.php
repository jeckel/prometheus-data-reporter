<?php
declare(strict_types=1);
/**
 * @author Julien Mercier-Rojas <julien@jeckel-lab.fr>
 * Created at : 09/06/19
 *
Sample :

```
omeglast_weather_celcius{location="Paris"} 25
omeglast_weather_humidity{location="Santiago"} 60
```
 */
class WeatherExport
{

    protected $config = [
        'metrics' => [
            'temp'     => ['format' => '%0.2f'],
            'humidity' => ['format' => '%d'],
            'pressure' => ['format' => '%d']
        ]
    ];

    /**
     * WeatherExport constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = array_replace_recursive($this->config, $config);
    }

    /**
     *
     */
    public function getData()
    {
        foreach($this->config['locations'] as $location) {
            $route = sprintf('https://api.jeckel-lab.fr/weather?q=%s&api-key=%s&units=metric', $location, getenv('API_KEY'));
//            $route = sprintf('http://api.openweathermap.org/data/2.5/weather?q=%s&APPID=%s&units=metric', $location, $this->config['appid']);
            $data = json_decode(file_get_contents($route), false);

            foreach($this->config['metrics'] as $metric=>$options) {
                printf("%s%s", $this->config['prefix'], $this->getExportLine($metric, $data->main->$metric, ['location' => $location]));
            }
        }
    }

    /**
     * @param string $metric
     * @param float  $value
     * @param array  $labels
     * @return string
     */
    protected function getExportLine(string $metric, float $value, array $labels = []): string
    {
        if (empty($labels)) {
            return sprintf("%s ".$this->getFormatFromMetric($metric)."\n", $metric, $value);
        }

        return sprintf("%s{%s} ".$this->getFormatFromMetric($metric)."\n", $metric, $this->getLabelAsString($labels), $value);
    }

    /**
     * @param string $metric
     * @return string
     */
    protected function getFormatFromMetric(string $metric): string
    {
        return $this->config['metrics'][$metric]['format'];
    }

    /**
     * @param array $labels
     * @return string*
     */
    protected function getLabelAsString(array $labels = []): string
    {
        return implode(',', array_map(function ($k, $v) { return sprintf('%s="%s"', $k, $v); }, array_keys($labels), $labels));
    }
}

$export = new WeatherExport([
    'prefix' => 'omeglast_weather_',
    'appid'  => getenv('OWM_APP_ID'),
    'locations' => ['Santiago,CL', 'Paris,FR'],
    'metrics' => [
        'temp'     => ['format' => '%0.2f'],
        'humidity' => ['format' => '%d'],
        'pressure' => ['format' => '%d']
    ]
]);

$export->getData();


$route = sprintf('https://api.jeckel-lab.fr/breezometer/airquality?api-key=%s&lat=48.755286&lon=2.409039&features=breezometer_aqi,pollutants_concentrations', getenv('API_KEY'));
$data = json_decode(file_get_contents($route), false);
foreach ($data->data->pollutants as $key=>$pollutant) {
    $metric = str_replace('/', '', sprintf('omeglast_pollutants_%s_%s', $key, $pollutant->concentration->units));
    printf('%s{location="Choisy le roi,FR"} %f'."\n", $metric, $pollutant->concentration->value);
}
