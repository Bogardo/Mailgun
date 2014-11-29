<?php namespace Bogardo\Mailgun;


use Bogardo\Mailgun\Lists\Mailinglist;
use Illuminate\Support\Collection;

class Lists extends MailgunApi {


    /**
     * @return Collection
     */
    public function all()
    {
        $items = new Collection([]);

        $results = $this->mailgun()->get("lists")->http_response_body;
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
