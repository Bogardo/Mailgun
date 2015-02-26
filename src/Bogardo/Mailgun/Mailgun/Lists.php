<?php namespace Bogardo\Mailgun\Mailgun;


use Bogardo\Mailgun\Mailgun\Lists\Mailinglist;
use Illuminate\Support\Collection;

class Lists extends MailgunApi {


    /**
     * Get all mailinglists
     *
     * @return Collection
     */
    public function all($params = [])
    {
        $items = new Collection([]);

        $results = $this->mailgun()->get("lists", $params)->http_response_body;
        if ($results) {
            foreach ($results->items as $item) {
                $mailinglist = new Mailinglist();
                $mailinglist->setList($item);
                $items->push($mailinglist);
            }
        }

        return $items;
    }

    /**
     * Get a single mailinglist by address
     *
     * @param string $listaddress
     *
     * @return \Bogardo\Mailgun\Lists\Mailinglist
     */
    public function get($listaddress)
    {
        $result = $this->mailgun()->get("lists/{$listaddress}")->http_response_body;
        if ($result) {
            $mailinglist = new Mailinglist();
            $mailinglist->setList($result->list);
            return $mailinglist;
        }
    }

    /**
     * @param array $params
     *
     * @return Mailinglist
     */
    public function create($params = [])
    {
        $mailinglist = new Mailinglist();
        $mailinglist->setList($this->mailgun()->post("lists", $params)->http_response_body->list);
        return $mailinglist;
    }

    /**
     * Update a mailinglist
     *
     * @param string $listaddress
     * @param array  $params
     *
     * @return Mailinglist
     */
    public function update($listaddress, $params = [])
    {
        $mailinglist = new Mailinglist($listaddress);
        return $mailinglist->update($params);
    }

    /**
     * Delete a mailinglist
     *
     * @param string $listaddress
     *
     * @return bool
     */
    public function delete($listaddress)
    {
        $mailinglist = new Mailinglist($listaddress);
        return $mailinglist->delete();
    }

    /**
     * Get mailinglist members
     *
     * @param string $listaddress
     * @param array  $params
     *
     * @return Collection
     */
    public function members($listaddress, $params = [])
    {
        $mailinglist = new Mailinglist($listaddress);
        return $mailinglist->members($params);
    }

    /**
     * Get a single mailinglist member
     *
     * @param $listaddress
     * @param $memberaddress
     *
     * @return Lists\Member
     */
    public function member($listaddress, $memberaddress)
    {
        $mailinglist = new Mailinglist($listaddress);
        return $mailinglist->member($memberaddress);
    }

    /**
     * Add a member to a mailinglist
     *
     * @param string $listaddress
     * @param array  $params
     *
     * @return \Bogardo\Mailgun\Lists\Member
     */
    public function addMember($listaddress, $params = [])
    {
        $mailinglist = new Mailinglist($listaddress);

        return $mailinglist->addMember($params);
    }

    /**
     * Add multiple members tot a mailinglist
     *
     * @param string $listaddress
     * @param array  $members
     * @param bool   $upsert
     *
     * @return Mailinglist
     */
    public function addMembers($listaddress, $members = [], $upsert = false)
    {
        $mailinglist = new Mailinglist($listaddress);

        return $mailinglist->addMembers($members, $upsert);
    }

    /**
     * Update a member in a mailinglist
     *
     * @param       $listaddress
     * @param       $memberaddress
     * @param array $params
     *
     * @return Lists\Member
     */
    public function updateMember($listaddress, $memberaddress, $params = [])
    {
        $mailinglist = new Mailinglist($listaddress);

        return $mailinglist->updateMember($memberaddress, $params);
    }


    /**
     * Delete a member of a mailinglist
     *
     * @param string $listaddress
     * @param string $memberaddress
     *
     * @return bool
     */
    public function deleteMember($listaddress, $memberaddress)
    {
        $mailinglist = new Mailinglist($listaddress);
        $mailinglist->deleteMember($memberaddress);

        return true;
    }

} 
