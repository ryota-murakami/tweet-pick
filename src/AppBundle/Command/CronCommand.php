<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Service\TwitterAPI;
use AppBundle\Entity\User;
use AppBundle\Entity\PastTimeline;

/**
 * 指定した日付の全ユーザーのタイムラインをDBに保存する
 *
 * TODO: テストが出来ないのでTwitterAPIをDIしたい
 *
 * app/console cron:SaveTargetDateTimeline yyyy-mm-dd
 */
class CronCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('cron:SaveTargetDateTimeline')
          ->setDescription('Save to DB All users Target Date Timeline.')
          ->addArgument(
            'date',
            InputArgument::REQUIRED,
            'what date do you want to save?'
          );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // 引数のフォーマットバリデーション
        if (!preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}/', $arg_date = $input->getArgument('date'))) {
            throw new \Exeption('invalid argument. date format must be yyyy-mm-dd. e.g. 2020-04-03');
        }

        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getEntityManager();
        // 登録ユーザーを取得
        $users = $doctrine->getRepository('AppBundle:User')->findAll();
        // tiwtter_apiのアクセストークン類
        $api_parameter = $this->getContainer()->getParameter('twitter_api');
        // タイムラインを取得する日付
        $targetDate = new \DateTime($arg_date);

        // メイン処理
        foreach ($users as $user) {
            // 昨日のタイムラインJsonを取得
            $twitterApi = new twitterApi($doctrine, $user, $api_parameter);
            $timeline_json = $twitterApi->findIdRangeByDate($targetDate)['timeline_json'];
            $encoded_json = json_encode($timeline_json); // DBにはJSON文字列をまるごと格納する

            // DBに保存するTimeLineオブジェクトを作成
            $pastTimeLine = new PastTimeline();
            $pastTimeLine->setUser($user);
            $pastTimeLine->setTimelineJson($encoded_json);
            $pastTimeLine->setDate($targetDate);
            $now = new \DateTime();
            $pastTimeLine->setCreateAt($now);
            $pastTimeLine->setUpdateAt($now);

            // DB保存処理
            $em->persist($pastTimeLine);
            $em->flush();
        }

        $output->writeln('Save PastTimeLine Complete.');
    }
}
