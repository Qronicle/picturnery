<?php
/**
 * PicturneryUser.php
 */

namespace Game\Picturnery;

use Game\UserInterface;

/**
 * PicturneryUser
 *
 * @copyright   2014 UniWeb bvba
 * @license     http://framework.uniweb.eu/license
 * @link        http://docs.uniweb.be/application-framework
 * @since       2014-12-29 0:01
 * @author      ruud.seberechts
 */
class PicturneryUser implements UserInterface
{
    protected $score = 0;

    /** @return array */
    public function toArray()
    {
        return array(
            'score' => $this->getScore(),
        );
    }


    /**
     * @param int $score
     */
    public function setScore($score)
    {
        $this->score = $score;
    }

    /**
     * @return int
     */
    public function getScore()
    {
        return $this->score;
    }

    public function addPoints($points)
    {
        $this->score += $points;
    }
}