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

$powerOn = null;

if(isset($_GET["powerOn"])){
    $powerOn = true;
    $pokeData = null;
    $pokeSpecies = null;
    $evoChain = null;
    $showMoves = null;
    $evolutionArray = null;
    $error = false;
}
if(isset($_GET["powerOff"])){
    $powerOn = false;
}

$pokeData = null;
$pokeSpecies = null;
$evoChain = null;
$showMoves = null;
$evolutionArray = null;
$error = false;
const API_URL = 'https://pokeapi.co/api/v2';
const MAX_POKE = 10290;

if (isset($_GET["php--search"]))
{
    $powerOn = true;
    $input = ($_GET["php--search"]);
    $input = strtolower($input);
        if (str_ends_with($input, 'mime')){
            $input = 'mr-mime';
        }
        if (str_ends_with($input, 'rime')){
            $input = 'mr-rime';
        }
        if (str_starts_with($input, 'mime')){
            $input = 'mime-jr';
        }
    $idInput = (int)$input;
    if ($idInput != null)
    {
        $input = max(1, min($idInput, MAX_POKE));
    }
    $pokeData = Pokemon::fetchPokemon($input);
    if($pokeData->name != '') {
        $pokeSpecies = pokeSpecies::getSpecies($input);
        $evoChain = evolutionChain::getEvos($input);
    } else {
        $pokeData = null;
        $pokeSpecies = null;
        $evoChain = null;
        $error = true;
    }
}

if (isset($_GET['random'])){
    $powerOn = true;
    $input = rand(1, 898);

    $pokeData = Pokemon::fetchPokemon($input);
    if($pokeData != null) {
        $pokeSpecies = pokeSpecies::getSpecies($input);
        $evoChain = evolutionChain::getEvos($input);
    } else {
        $pokeData = null;
        $pokeSpecies = null;
        $evoChain = null;
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
        if ($pokedata!=null){
            $pokedata = json_decode($pokedata, true);
            return new Pokemon($pokedata['name'], $pokedata['id'], $pokedata['moves'], $pokedata['sprites'], $pokedata['height'], $pokedata['weight'], $pokedata['types'], $pokedata['species']['url']);
        } else {
            return new Pokemon();
        }

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
                if(count($evoChain['evolves_to'])>=1){
                    foreach ($evoChain['evolves_to'] as $evo){
                        $evo2Name = $evo['species']['name'];
                        $evo2Data = file_get_contents(API_URL . '/pokemon/' . $evo2Name);
                        if ($evo2Data == null){
                            $evo2species = $evo['species']['url'];
                            $evo2ID = substr($evo2species, -4, 3);
                            $evo2Data = file_get_contents(API_URL . '/pokemon/' . $evo2ID);
                        }
                        $evo2Data = json_decode($evo2Data, true);
                        $evo2Sprite = $evo2Data['sprites']['front_default'];
                        $evo2 = [$evo2Name, $evo2Sprite];
                        array_push($evolutions, $evo2);
                        if(count($evo['evolves_to'])>0){
                            foreach($evo['evolves_to'] as $evo2){
                                $evo3Name = $evo2['species']['name'];
                                $evo3Data = file_get_contents(API_URL . '/pokemon/' . $evo3Name);
                                $evo3Data = json_decode($evo3Data, true);
                                $evo3Sprite = $evo3Data['sprites']['front_default'];
                                $evo3 = [$evo3Name, $evo3Sprite];
                                array_push($evolutions, $evo3);
                            }
                        }
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
        <div class="<?php
        if ($powerOn==false){
            echo 'logoOff';
        } else {
            echo 'logo';
        }
        ?>"></div>
        <div class="bg_curve1_left"></div>
        <div id="bg_curve2_left"></div>
        <div class="curve1_left">
            <?php
            if($powerOn != true){
                echo '<a href="index.php?powerOn" id="powerButton" class="powerButtonOff"><div id="reflect"></div></a>
                    <div id="redLight" class="powerButtonOff"></div>
                    <div id="yellowLight" class="powerButtonOff"></div>
                    <div id="greenLight" class="powerButtonOff"></div>';
            } else {
                echo '<a href="index.php?powerOff" id="powerButton" class="powerButtonOn"><div id="reflect"></div></a>
                    <div id="redLight" class="redLightOn"></div>
                    <div id="yellowLight" class="yellowLightOn"></div>
                    <div id="greenLight" class="greenLightOn"></div>';
            }
            ?>
        </div>
        <div id="curve2_left">
            <div id="junction">
                <div id="junction1"></div>
                <div id="junction2"></div>
            </div>
        </div>
        <div class="
        <?php
        if ($powerOn!=true){
            echo 'screenOff';
        } else {
            echo 'screen';
        }
        ?>
        ">
            <div id="topPicture">
                <div id="buttontopPicture1"></div>
                <div id="buttontopPicture2"></div>
            </div>
            <div id="picture">
                <img id="js--pokeImage" alt="pokemon" height="170" src="<?php
                if ($pokeData->id != 0){
                        echo $pokeData->sprites['other']['official-artwork']['front_default'];
                } else {
                    if($error != true){
                        echo 'IMG/oak.gif';
                    } else {
                        echo ' IMG/error.png';
                    }

                }
                 ?>" />
            </div>
            <?php
            if($pokeData->id != 0){
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
        <a href="index.php?random=true" id="js--randomPokemon" class="
        <?php
        if ($powerOn!=true){
            echo 'randomPokemonOff';
        } else {
            echo 'randomPokemon';
        }
        ?>
        ">
            <img id="random" src="IMG/random.png" alt="random pokemon">
        </a>


        <div class=" <?php
        if ($powerOn!=true){
            echo 'barbutton1Off';
        } else {
            echo 'barbutton1';
        }
        ?>"></div>
        <div class="<?php
        if ($powerOn!=true){
            echo 'barbutton2Off';
        } else {
            echo 'barbutton2';
        }
        ?>"></div>
        <div class="<?php
        if ($powerOn!=true){
            echo 'crossOff';
        } else {
            echo 'cross';
        }
        ?>">
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
    <div class="<?php
    if ($powerOn!=true){
        echo 'rightOff';
    } else {
        echo 'right';
    }
    ?>">
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
                        $evoArray = [];
                        $evoArray[0] = $evoChain->evolutions[0];
                        $evoArray2 = $evoChain->evolutions;
                        array_shift($evoArray2);
                        shuffle($evoArray2);
                        foreach($evoArray2 as $e2){
                            array_push($evoArray, $e2);
                        }
                        echo '<div class="js--pokeSpriteTooltip evolution evolutionOn tooltip" title="">
                        <img src="' . $evoArray[0][1] . '" alt="sprite1"  class="js--pokeSprite">
                        <span class="tooltiptext">' . $evoArray[0][0] . '</span>
                        </div>';
                        for ($i=1;$i<6;$i++){
                            echo '<div class="js--pokeSpriteTooltip evolution evolutionOn tooltip" title="">
                            <img src="' . $evoArray[$i][1] . '" alt="sprite1"  class="js--pokeSprite">
                            <span class="tooltiptext">' . $evoArray[$i][0] . '</span>
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