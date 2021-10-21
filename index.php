<!DOCTYPE html>
<html lang="en">
<head>
    <!--metadata-->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="A pokedex using php and the pokemon api">
    <meta name="keywords" content="pokemon, pokedex, api, php, BeCode">
    <meta name="author" content="Sven Vander Mierde">

    <!--favicon-->
    <link rel="icon" type="image/png" sizes="32x32" href="IMG/pikachuIcon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="IMG/pikachuIcon.png">

    <!--font and stylesheet-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/style.css">

    <title>Pokedex</title>
</head>
<body>

<?php

$pokeData = null;
$pokeSpecies = null;
$evoChain = null;
$showMoves = null;
$evolutionArray = null;
const API_URL = 'https://pokeapi.co/api/v2';
const MAX_POKE = 10290;

if (isset($_GET["php--search"]))
{
    $input = ($_GET["php--search"]);
    $idInput = (int)$input;
    if ($idInput != null)
    {
        $input = max(1, min($idInput, MAX_POKE));
    }
    $pokeData = Pokemon::fetchPokemon($input);
    $name = $pokeData->name;
    if ($name!= null) {
        $pokeSpecies = pokeSpecies::getSpecies($input);
        $evoChain = evolutionChain::getEvos($input);
    } else {
        $pokeSpecies = null;
        $evoChain = null;
    }
}

if (isset($_GET['random'])){
    $input = rand(1, 898);

    $pokeData = Pokemon::fetchPokemon($input);
    if ($pokeData->name != null){
        $pokeSpecies = pokeSpecies::getSpecies($input);
        $evoChain = evolutionChain::getEvos($input);
    }

}

class Pokemon
{
    public string $name;
    public int $id = 0;
    public array $moves = [];
    public array $sprites = [];
    public int $height = 0;
    public int $weight = 0;
    public array $types = [];
    public string $speciesurl = '';

    function __construct(string $name = '', int $id = 0, array $moves = [], array $sprites = [], int $height = 0, int $weight = 0, array $types = [], string $speciesurl = '')
    {
        $this->name = $name;
        $this->id = $id;
        $this->moves = $moves;
        $this->sprites = $sprites;
        $this->height = $height;
        $this->weight = $weight;
        $this->types = $types;
        $this->speciesurl = $speciesurl;
    }

    static function fetchPokemon($input): Pokemon
    {
        $pokedata = file_get_contents(API_URL . '/pokemon/' . $input);
        if($pokedata!=null){
            $pokedata = json_decode($pokedata, true);
        } else {
            $pokedata['name'] = null;
            $pokedata['id'] = '';
            $pokedata['moves'] = [];
            $pokedata['sprites'] = [];
            $pokedata['height'] = 0;
            $pokedata['weight'] = 0;
            $pokedata['types'] = [];
            $pokedata['species']['url'] = '';
        }
        return new Pokemon($pokedata['name'], $pokedata['id'], $pokedata['moves'], $pokedata['sprites'], $pokedata['height'], $pokedata['weight'], $pokedata['types'], $pokedata['species']['url']);

    }
}

class pokeSpecies
{
    public array $flavor_text_entries = [];

    function __construct(array $flavorText = [])
    {
        $this->flavor_text_entries = $flavorText;
    }

    static function getSpecies($input): pokeSpecies
    {
        $pokeData = Pokemon::fetchPokemon($input);
        $speciesURL= $pokeData->speciesurl;
        $pokespecies = file_get_contents($speciesURL);
        $pokespecies = json_decode($pokespecies, true);

        return new pokeSpecies($pokespecies['flavor_text_entries']);
    }
}

class evolutionChain
{
    public array $evolutions = [];

    function __construct(array $evolutions = [])
    {
        $this->evolutions = $evolutions;
    }

    static function getevos($input): evolutionChain
    {
        $pokeData = Pokemon::fetchPokemon($input);
        $speciesURL= $pokeData->speciesurl;
        $pokespecies = file_get_contents($speciesURL);
        $pokespecies = json_decode($pokespecies, true);
        $evoData = file_get_contents($pokespecies['evolution_chain']['url']);
        $evoData = json_decode($evoData, true);
        $evoChain = $evoData['chain'];
        $evolutions =[];
        if (count($evoChain) > 0){
            $evo1Name = $evoChain['species']['name'];
            $evo1Data = file_get_contents(API_URL . '/pokemon/' . $evo1Name);
            if ($evo1Data!= null){
                $evo1Data = json_decode($evo1Data, true);
                $evo1Sprite = $evo1Data['sprites']['front_default'];
                $evo1 = [$evo1Name, $evo1Sprite];
                array_push($evolutions, $evo1);
                if(count($evoChain['evolves_to'])==1){
                    $evo2Name = $evoChain['evolves_to'][0]['species']['name'];
                    $evo2Data = file_get_contents(API_URL . '/pokemon/' . $evo2Name);
                    $evo2Data = json_decode($evo2Data, true);
                    $evo2Sprite = $evo2Data['sprites']['front_default'];
                    $evo2 = [$evo2Name, $evo2Sprite];
                    array_push($evolutions, $evo2);
                    if(count($evoChain['evolves_to'][0]['evolves_to'])==1){
                        $evo3Name = $evoChain['evolves_to'][0]['evolves_to'][0]['species']['name'];
                        $evo3Data = file_get_contents(API_URL . '/pokemon/' . $evo3Name);
                        $evo3Data = json_decode($evo3Data, true);
                        $evo3Sprite = $evo3Data['sprites']['front_default'];
                        $evo3 = [$evo3Name, $evo3Sprite];
                        array_push($evolutions, $evo3);
                    }
                } elseif (count($evoChain['evolves_to'])>1){
                    foreach($evoChain['evolves_to'] as $evo){
                        $evoName = $evo['species']['name'];
                        $evoData = file_get_contents(API_URL . '/pokemon/' . $evoName);
                        if ($evoData == null) {
                            $evoName = $pokeData->name;
                            $evoData = file_get_contents(API_URL . '/pokemon/' . $evoName);
                        }
                        $evoData = json_decode($evoData, true);
                        $evoSprite = $evoData['sprites']['front_default'];
                        $evo = [$evoName, $evoSprite];
                        array_push($evolutions, $evo);

                    }
                }
            }

        }


        return new evolutionChain($evolutions);
    }


}
?>

<div class="pokedex">
    <div class="left">
        <div class="logo"></div>
        <div class="bg_curve1_left"></div>
        <div id="bg_curve2_left"></div>
        <div class="curve1_left">
            <a id="powerButton" class="powerButtonOn">
                <div id="reflect"> </div>
            </a>
            <div id="redLight" class="redLightOn"></div>
            <div id="yellowLight" class="yellowLightOn"></div>
            <div id="greenLight" class="greenLightOn"></div>
        </div>
        <div id="curve2_left">
            <div id="junction">
                <div id="junction1"></div>
                <div id="junction2"></div>
            </div>
        </div>
        <div class="screen">
            <div id="topPicture">
                <div id="buttontopPicture1"></div>
                <div id="buttontopPicture2"></div>
            </div>
            <div id="picture">
                <img id="js--pokeImage" alt="pokemon" height="170" src="<?php
                if ($pokeData->id != 0){
                    if ($pokeData->name != null){
                        echo $pokeData->sprites['other']['official-artwork']['front_default'];
                    } else {
                        echo 'IMG/error.png';
                    }

                } else {
                    echo 'IMG/oak.gif';
                }
                 ?>" />
            </div>
            <?php
            if($pokeData->id != 0 &&$pokeData->name != null){
                echo' <div id="js--pokeID">' . $pokeData->id . '</div>';
            }
            ?>
            <div id="speakers">
                <div class="sp"></div>
                <div class="sp"></div>
                <div class="sp"></div>
                <div class="sp"></div>
            </div>
        </div>
            <a href="index.php?random=true" id="js--randomPokemon" class="randomPokemon">
                <img id="random" src="IMG/random.png" alt="random pokemon">
            </a>


        <div class="barbutton1"></div>
        <div class="barbutton2"></div>
        <div class="cross">
            <div id="leftcross">
                <div id="leftT"></div>
            </div>
            <div id="topcross">
                <div id="upT"></div>
            </div>
            <div id="rightcross">
                <div id="rightT"></div>
            </div>
            <div id="midcross">
                <div id="midCircle"></div>
            </div>
            <div id="botcross">
                <div id="downT"></div>
            </div>
        </div>
    </div>
    <div class="right">
        <div id="stats">
            <div id="base-stats">
                <div id="stats-left">
                    <div class="js--stat"><strong>Name: </strong><?php if($pokeData->id != ''){ echo $pokeData->name; }?></div>
                    <div class="js--stat"><strong>Type: </strong>
                        <?php if($pokeData->id != '') {
                            for ($i=0; $i<count($pokeData->types);$i++){
                                echo $pokeData->types[$i]['type']['name'] . ' ';
                            }

                        }
                        ?>
                    </div>
                    <div class="js--stat"><strong>Height: </strong><?php if($pokeData->id != ''){ echo $pokeData->height; }?></div>
                    <div class="js--stat"><strong>Weight: </strong><?php if($pokeData->id != ''){ echo $pokeData->weight; }?></div>
                </div>
                <div id="stats-right">
                    <div class="js--stat"><strong id="move">Moves: </strong>
                        <?php
                        if ($pokeData != null){
                            $movesArray = $pokeData->moves;
                            if(count($movesArray)<=4){
                                for($i=0; $i<count($movesArray);$i++){
                                    echo '<br>' . $movesArray[$i]['move']['name'];
                                }
                            } else {
                                for ($i=0;$i<4;$i++){
                                    echo '<br>' . $movesArray[rand(0, count($movesArray))]['move']['name'];
                                }

                            }
                        }

                        ?>
                    </div>
                </div>
            </div>
            <div id="flavorText">
                <div class="js--stat" id="js--pokeFlavorText">
                    <?php
                    if($pokeData->id != 0) {
                        $entries = $pokeSpecies->flavor_text_entries;
                        $text ='';
                        for ($i=0;$i<count($entries);$i++){
                            $lang = $entries[$i]['language']['name'];
                            if ($lang == 'en'){
                                $text = $entries[$i]['flavor_text'];
                                $i = count($entries);
                            }
                        }
                        echo '<marquee>' . strtolower($text) . '</marquee>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <div id="evolutions">
            <?php
            if ($pokeData != null && count($evoChain->evolutions)!=0){
                    $evoArray = $evoChain->evolutions;
                    if (count($evoArray)<=6){
                        $x = 6;
                        foreach ($evoArray as $evolution){
                            echo '<div class="js--pokeSpriteTooltip evolution evolutionOn tooltip" title="">
                            <img src="' . $evolution[1] . '" alt="sprite1"  class="js--pokeSprite">
                            <span class="tooltiptext">' . $evolution[0] . '</span>
                            </div>';
                            $x--;
                        }
                        if ($x < 6){
                            for ($i=$x; $i>0;$i--){
                                echo '<div class="js--pokeSpriteTooltip evolution evolutionOff" title=""></div>';
                            }
                        }
                    } else{
                        echo '<div class="js--pokeSpriteTooltip evolution evolutionOn tooltip" title="">
                        <img src="' . $evoArray[0][1] . '" alt="sprite1"  class="js--pokeSprite">
                        <span class="tooltiptext">' . $evoArray[0][0] . '</span>
                        </div>';
                        for ($i=1;$i<6;$i++){
                            echo '<div class="js--pokeSpriteTooltip evolution evolutionOn tooltip" title="">
                            <img src="' . $evoArray[rand(1, count($evoArray))][1] . '" alt="sprite1"  class="js--pokeSprite">
                            <span class="tooltiptext">' . $evoArray[rand(1, count($evoArray))][0] . '</span>
                            </div>';
                        }
                    }

            } else {
                for ($i=0; $i<6;$i++){
                    echo '<div class="js--pokeSpriteTooltip evolution evolutionOff" title=""></div>';
                }
            }

            ?>
        </div>
        <form method="get">
            <div id="search">
                <label for="js--search"></label>
                <input id="js--search" placeholder="enter ID or name" name="php--search">
            </div>
            <button id="js--searchButton" type="submit">
                <img src="IMG/find.png" class="button-image" alt="findButton">
            </button>
        </form>
        <div id="minipowerButton4"></div>
        <div id="minipowerButton5"></div>
        <div id="barbutton3"></div>
        <div id="barbutton4"></div>

        <div id="js--helpButton">
            <img src="IMG/help.png" class="button-image" alt="helpButton">
        </div>
        <div id="bg_curve1_right"></div>
        <div id="bg_curve2_right"></div>
        <div id="curve1_right"></div>
        <div id="curve2_right"></div>
        </div>
</div>

</body>
</html>