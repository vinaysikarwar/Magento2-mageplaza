<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_Blog
 * @copyright   Copyright (c) 2016 Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\Blog\Block;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Mageplaza\Blog\Model\CommentFactory;
use Mageplaza\Blog\Model\LikeFactory;
use Magento\Framework\View\Element\Template\Context;
use Mageplaza\Blog\Helper\Data as HelperData;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Cms\Model\Template\FilterProvider;
use Mageplaza\Blog\Model\Config\Source\MetaRobots;
use Magento\Framework\Image\AdapterFactory;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class Frontend
 *
 * @package Mageplaza\Blog\Block
 */
class Frontend extends Template
{

	/**
	 * @type \Mageplaza\Blog\Helper\Data
	 */
	public $helperData;

	/**
	 * @type \Magento\Store\Model\StoreManagerInterface
	 */
	public $store;

	/**
	 * @type \Magento\Framework\Stdlib\DateTime\DateTime
	 */
	public $dateTime;

	/**
	 * @var \Mageplaza\Blog\Model\Config\Source\MetaRobots
	 */
	public $mpRobots;

	/**
	 * @var FilterProvider
	 */
	public $filterProvider;

	/**
	 * @var \Mageplaza\Blog\Model\CommentFactory
	 */
	public $cmtFactory;

	/**
	 * @var \Mageplaza\Blog\Model\LikeFactory
	 */
	public $likeFactory;

	/**
	 * @var \Magento\Customer\Api\CustomerRepositoryInterface
	 */
	public $customerRepository;

	/**
	 * @var
	 */
	public $commentTree;

	/**
	 * @var \Magento\Framework\ObjectManagerInterface
	 */
	public $objectManager;
	/**
	 * @var \Magento\Framework\Filesystem
	 */
	protected $_filesystem;

	/**
	 * @var \Magento\Framework\Image\AdapterFactory
	 */
	protected $_imageFactory;

	/**
	 * @var \Magento\Framework\View\Design\Theme\ThemeProviderInterface
	 */
	protected $_themeProvider;

	/**
	 * Frontend constructor.
	 * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
	 * @param \Mageplaza\Blog\Model\CommentFactory $commentFactory
	 * @param \Mageplaza\Blog\Model\LikeFactory $likeFactory
	 * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
	 * @param \Mageplaza\Blog\Model\Config\Source\MetaRobots $metaRobots
	 * @param \Magento\Framework\View\Element\Template\Context $context
	 * @param \Mageplaza\Blog\Helper\Data $helperData
	 * @param \Magento\Framework\View\Element\Template\Context $templateContext
	 * @param \Magento\Cms\Model\Template\FilterProvider $filterProvider
	 * @param \Magento\Framework\Image\AdapterFactory $imageFactory
	 * @param \Magento\Framework\View\Design\Theme\ThemeProviderInterface $_themeProvider
	 * @param \Magento\Framework\ObjectManagerInterface $objectManager
	 * @param array $data
	 */
	public function __construct(
		DateTime $dateTime,
		CommentFactory $commentFactory,
		LikeFactory $likeFactory,
		CustomerRepositoryInterface $customerRepository,
		MetaRobots $metaRobots,
		Context $context,
		HelperData $helperData,
		TemplateContext $templateContext,
		FilterProvider $filterProvider,
		AdapterFactory $imageFactory,
		ThemeProviderInterface $_themeProvider,
		ObjectManagerInterface $objectManager,
		array $data = []
	)
	{
		$this->dateTime           = $dateTime;
		$this->mpRobots           = $metaRobots;
		$this->cmtFactory         = $commentFactory;
		$this->likeFactory        = $likeFactory;
		$this->customerRepository = $customerRepository;
		$this->helperData         = $helperData;
		$this->store              = $templateContext->getStoreManager();
		$this->filterProvider     = $filterProvider;
		$this->_filesystem        = $context->getFilesystem();
		$this->_imageFactory      = $imageFactory;
		$this->_themeProvider     = $_themeProvider;
		$this->objectManager      = $objectManager;
		parent::__construct($context, $data);
	}

	/**
	 * @return mixed
	 */
	public function getCurrentPost()
	{
		return $this->helperData->getPost($this->getRequest()->getParam('id'));
	}

	/**
	 * @param $post
	 *
	 * @return string
	 */
	public function getUrlByPost($post)
	{
		return $this->helperData->getUrlByPost($post);
	}

	/**
	 * @param $image
	 *
	 * @return string
	 */
	public function getImageUrl($image)
	{
		return $this->helperData->getImageUrl($image);
	}

	/**
	 * @param $createdAt
	 *
	 * @return \DateTime
	 */
	public function getCreatedAtStoreDate($createdAt)
	{
		return $this->_localeDate->scopeDate(
			$this->_storeManager->getStore(),
			$createdAt,
			true
		);
	}

	/**
	 * @param $post
	 *
	 * @return null|string
	 */
	public function getPostCategoryHtml($post)
	{
		return $this->helperData->getPostCategoryHtml($post);
	}

	/**
	 * @param $code
	 *
	 * @return mixed
	 */
	public function getBlogConfig($code)
	{
		return $this->helperData->getBlogConfig($code);
	}

	/**
	 * filter post by store
	 * return true/false
	 */
	public function filterPost($post)
	{
		$storeId     = $this->store->getStore()->getId();
		$postStoreId = $post->getStoreIds() != null ? explode(
			',',
			$post->getStoreIds()
		) : '-1';
		if (is_array($postStoreId)
			&& (in_array($storeId, $postStoreId)
				|| in_array('0', $postStoreId))
		) {
			return true;
		}

		return false;
	}

	public function getSeoConfig($code, $storeId = null)
	{
		return $this->helperData->getSeoConfig($code, $storeId);
	}

	/**
	 * @return $this
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	protected function _prepareLayout()
	{
		$actionName       = $this->getRequest()->getFullActionName();
		$breadcrumbs      = $this->getLayout()->getBlock('breadcrumbs');
		$breadcrumbsLabel = ucfirst(
			$this->helperData->getBlogConfig('general/name')
				?: \Mageplaza\Blog\Helper\Data::DEFAULT_URL_PREFIX
		);
		$breadcrumbsLink  = $this->helperData->getBlogConfig(
			'general/url_prefix'
		) ?: \Mageplaza\Blog\Helper\Data::DEFAULT_URL_PREFIX;
		if ($breadcrumbs) {
			if ($actionName == 'mpblog_post_index'
				|| $actionName == 'mpblog_month_view'
			) {
				$breadcrumbs->addCrumb(
					'home',
					['label' => __('Home'), 'title' => __('Go to Home Page'),
					 'link'  => $this->_storeManager->getStore()->getBaseUrl()]
				)->addCrumb(
					$this->helperData->getBlogConfig('general/url_prefix'),
					['label' => $breadcrumbsLabel,
					 'title' => $this->helperData->getBlogConfig(
						 'general/url_prefix'
					 )]
				);
				$this->applySeoCode();
			} elseif ($actionName == 'mpblog_post_view') {
				$post = $this->getCurrentPost();
				if ($this->filterPost($post)) {
					$category = $post->getSelectedCategoriesCollection()
						->addFieldToFilter('enabled', 1)->getFirstItem();
					$breadcrumbs->addCrumb(
						'home',
						['label' => __('Home'),
						 'title' => __('Go to Home Page'),
						 'link'  => $this->_storeManager->getStore()
							 ->getBaseUrl()]
					);
					/* $breadcrumbs->addCrumb(
						$this->helperData->getBlogConfig('general/url_prefix'),
						['label'   => $breadcrumbsLabel,
						 'title'   => $this->helperData->getBlogConfig(
							 'general/url_prefix'
						 ), 'link' => $this->_storeManager->getStore()
								->getBaseUrl() . $breadcrumbsLink]
					); */
					if ($category->getId()) {
						$breadcrumbs->addCrumb(
							$category->getUrlKey(),
							['label' => ucfirst($category->getName()),
							 'title' => $category->getName(),
							 'link'  => $this->helperData->getCategoryUrl(
								 $category->getUrlKey()
							 )]
						);
					}
					$breadcrumbs->addCrumb(
						$post->getUrlKey(),
						['label' => ucfirst($post->getName()),
						 'title' => $post->getName()]
					);
					$this->applySeoCode($post);
				}
			} elseif ($actionName == 'mpblog_category_view') {
				$category = $this->helperData->getCategoryByParam(
					'id',
					$this->getRequest()->getParam('id')
				);
				$breadcrumbs->addCrumb(
					'home',
					['label' => __('Home'), 'title' => __('Go to Home Page'),
					 'link'  => $this->_storeManager->getStore()->getBaseUrl()]
				);
				
				$breadcrumbs->addCrumb(
					'learning-center',
					['label' => __('Learning Center'), 'title' => __('Learning Center'),
					 'link'  => $this->getUrl('learning-center')]
				);
				
				$breadcrumbs->addCrumb(
					$category->getUrlKey(),
					['label' => ucfirst($category->getName()),
					 'title' => $category->getName(),]
				);
				$this->applySeoCode($category);
			} elseif ($actionName == 'mpblog_tag_view') {
				$tag = $this->helperData->getTagByParam(
					'id',
					$this->getRequest()->getParam('id')
				);
				$breadcrumbs->addCrumb(
					'home',
					['label' => __('Home'), 'title' => __('Go to Home Page'),
					 'link'  => $this->_storeManager->getStore()->getBaseUrl()]
				)->addCrumb(
					$this->helperData->getBlogConfig('general/url_prefix'),
					['label'   => $breadcrumbsLabel,
					 'title'   => $this->helperData->getBlogConfig(
						 'general/url_prefix'
					 ), 'link' => $this->_storeManager->getStore()->getBaseUrl()
						. $breadcrumbsLink]
				)->addCrumb(
					'Tag' . $tag->getId(),
					['label' => ucfirst($tag->getName()),
					 'title' => $tag->getName()]
				);
				$this->applySeoCode($tag);
			} elseif ($actionName == 'mpblog_topic_view') {
				$topic = $this->helperData->getTopicByParam(
					'id',
					$this->getRequest()->getParam('id')
				);
				$breadcrumbs->addCrumb(
					'home',
					['label' => __('Home'), 'title' => __('Go to Home Page'),
					 'link'  => $this->_storeManager->getStore()->getBaseUrl()]
				)->addCrumb(
					$this->helperData->getBlogConfig('general/url_prefix'),
					['label'   => $breadcrumbsLabel,
					 'title'   => $this->helperData->getBlogConfig(
						 'general/url_prefix'
					 ), 'link' => $this->_storeManager->getStore()->getBaseUrl()
						. $breadcrumbsLink]
				)->addCrumb(
					'topic' . $topic->getId(),
					['label' => ucfirst($topic->getName()),
					 'title' => $topic->getName()]
				);
				$this->applySeoCode($topic);
			} elseif ($actionName == 'mpblog_author_view') {
				$author = $this->helperData->getAuthorByParam(
					'id',
					$this->getRequest()->getParam('id')
				);
				$breadcrumbs->addCrumb(
					'home',
					['label' => __('Home'), 'title' => __('Go to Home Page'),
					 'link'  => $this->_storeManager->getStore()->getBaseUrl()]
				)->addCrumb(
					$this->helperData->getBlogConfig('general/url_prefix'),
					['label'   => $breadcrumbsLabel,
					 'title'   => $this->helperData->getBlogConfig(
						 'general/url_prefix'
					 ), 'link' => $this->_storeManager->getStore()->getBaseUrl()
						. $breadcrumbsLink]
				)->addCrumb(
					'author' . $author->getId(),
					['label' => __('Author'), 'title' => __('Author')]
				);
				$pageMainTitle = $this->getLayout()->getBlock(
					'page.main.title'
				);
				$pageMainTitle->setPageTitle('About Author');
				$this->pageConfig->getTitle()->set($author->getName());
			} elseif ($actionName == 'mpblog_sitemap_index') {
				$breadcrumbs->addCrumb(
					'home',
					['label' => __('Home'), 'title' => __('Go to Home Page'),
					 'link'  => $this->_storeManager->getStore()->getBaseUrl()]
				)->addCrumb(
					$this->helperData->getBlogConfig('general/url_prefix'),
					['label'   => $breadcrumbsLabel,
					 'title'   => $this->helperData->getBlogConfig(
						 'general/url_prefix'
					 ), 'link' => $this->_storeManager->getStore()->getBaseUrl()
						. $breadcrumbsLink]
				)->addCrumb(
					'SiteMap',
					['label' => __('Sitemap'), 'title' => __('Sitemap')]
				);
				$pageMainTitle = $this->getLayout()->getBlock(
					'page.main.title'
				);
				$pageMainTitle->setPageTitle('Site Map');
				$this->pageConfig->getTitle()->set('Site Map');
			}
		}


		return parent::_prepareLayout();
	}

	/**
	 * @param null $post
	 *
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	public function applySeoCode($post = null)
	{
		$description = $this->getSeoConfig('meta_description');
		$keywords    = $this->getSeoConfig('meta_keywords');

		if ($post) {
			$title = $post->getMetaTitle();
			$this->setPageData($title, 1, $post->getName());

			$description = $post->getMetaDescription();
			$this->setPageData($description, 2, $description);

			$keywords = $post->getMetaKeywords();
			$this->setPageData($keywords, 3, $keywords);

			$robot = $post->getMetaRobots();
			$array = $this->mpRobots->getOptionArray();
//			\Zend_Debug::dump($array[$robot]);
//			if ($keywords) {
			$this->setPageData($array[$robot], 4);
//			}
			$pageMainTitle = $this->getLayout()->getBlock('page.main.title');
			if ($pageMainTitle) {
				$pageMainTitle->setPageTitle($post->getName());
			}
		} else {
			$title = $this->getSeoConfig('meta_title')
				?: $this->helperData->getBlogConfig('general/name');
			$this->setPageData($title, 1, __('Blog'));

			$this->setPageData($description, 2);

			$this->setPageData($keywords, 3);

			$pageMainTitle = $this->getLayout()->getBlock('page.main.title');
			if ($pageMainTitle) {
				$pageMainTitle->setPageTitle(
					$this->helperData->getBlogConfig('general/name')
				);
			}
		}
	}

	/**
	 * @param      $data
	 * @param      $type
	 * @param null $name
	 *
	 * @return string|void
	 */
	public function setPageData($data, $type, $name = null)
	{
		if ($data) {
			return $this->setDataFromType($data, $type);
		}

		return $this->setDataFromType($name, $type);
	}

	/**
	 * @param $data
	 * @param $type
	 * @return $this|string|void
	 */
	public function setDataFromType($data, $type)
	{
		switch ($type) {
			case 1:
				return $this->pageConfig->getTitle()->set($data);
				break;
			case 2:
				return $this->pageConfig->setDescription($data);
				break;
			case 3:
				return $this->pageConfig->setKeywords($data);
				break;
			case 4:
				return $this->pageConfig->setRobots($data);
				break;
		}

		return '';
	}

	/**
	 * @param null $type
	 * @param null $id
	 *
	 * @return array|string
	 */
	public function getBlogPagination($type = null, $id = null)
	{
		$page     = $this->getRequest()->getParam('p');
		$postList = '';
		if ($type == null) {
			$postList = $this->helperData->getPostList();
		} elseif ($type == \Mageplaza\Blog\Helper\Data::CATEGORY) {
			$postList = $this->helperData->getPostList(
				\Mageplaza\Blog\Helper\Data::CATEGORY,
				$id
			);
		} elseif ($type == \Mageplaza\Blog\Helper\Data::TAG) {
			$postList = $this->helperData->getPostList(
				\Mageplaza\Blog\Helper\Data::TAG,
				$id
			);
		} elseif ($type == \Mageplaza\Blog\Helper\Data::TOPIC) {
			$postList = $this->helperData->getPostList(
				\Mageplaza\Blog\Helper\Data::TOPIC,
				$id
			);
		} elseif ($type == \Mageplaza\Blog\Helper\Data::AUTHOR) {
			$postList = $this->helperData->getPostList(
				\Mageplaza\Blog\Helper\Data::AUTHOR,
				$id
			);
		} elseif ($type == \Mageplaza\Blog\Helper\Data::MONTHLY) {
			$postList = $this->helperData->getPostList(
				\Mageplaza\Blog\Helper\Data::MONTHLY,
				$id
			);
		}

		if ($postList != '' && is_array($postList)) {
			$limit     = (int)$this->getBlogConfig('general/pagination') ?: 1;
			$numOfPost = count($postList);
			$numOfPage = 1;
			$countPost = count($postList);
			if ($countPost > $limit) {
				$numOfPage = ($numOfPost % $limit != 0) ? ($numOfPost / $limit)
					+ 1 : ($numOfPost / $limit);

				return $this->getPostPerPage(
					$page,
					$numOfPage,
					$limit,
					$postList
				);
			}

			array_unshift($postList, $numOfPage);

			return $postList;
		}

		return '';
	}

	/**
	 * @param null $page
	 * @param       $numOfPage
	 * @param       $limit
	 * @param array $array
	 *
	 * @return array
	 */
	public function getPostPerPage(
		$page = null,
		$numOfPage,
		$limit,
		$array = []
	)
	{
		$results    = [];
		$firstIndex = 0;
		$lastIndex  = $limit - 1;
		if ($page) {
			if ($page > $numOfPage || $page < 1) {
				$page = 1;
			}

			$firstIndex = $limit * $page - $limit;
			$lastIndex  = $firstIndex + $limit - 1;
			if (!isset($array[$lastIndex])) {
				for ($i = $lastIndex; $i >= $firstIndex; $i--) {
					if (isset($array[$i])) {
						$lastIndex = $i;
						break;
					}
				}
			}
		}

		for ($i = $firstIndex; $i <= $lastIndex; $i++) {
			array_push($results, $array[$i]);
		}

		array_unshift($results, $numOfPage);

		return $results;
	}

	/**
	 * get sidebar config
	 *
	 * @param $code
	 * @param $storeId
	 *
	 * @return mixed
	 */
	public function getSidebarConfig($code, $storeId = null)
	{
		return $this->helperData->getSidebarConfig($code, $storeId);
	}

	public function getAuthorByPost($authorId)
	{
		return $this->helperData->getAuthorByPost($authorId);
	}

	public function getAuthorUrl($author)
	{
		return $this->helperData->getAuthorUrl($author);
	}

	public function getAuthorImageUrl($image)
	{
		return $this->helperData->getAuthorImageUrl($image);
	}

	/**
	 * check customer is logged in or not
	 */
	public function isLoggedIn()
	{
		return $this->helperData->isLoggedIn();
	}

	/**
	 * get login url
	 */
	public function getLoginUrl()
	{
		return $this->helperData->getLoginUrl();
	}

	/**
	 * @param $postId
	 * @return array
	 */
	public function getPostComments($postId)
	{
		$result   = [];
		$comments = $this->cmtFactory->create()->getCollection()
			->addFieldToFilter('post_id', $postId);
		foreach ($comments as $comment) {
//        	\Zend_Debug::dump($comment->getData());die();
			array_push($result, $comment->getData());
		}

		return $result;
	}

	/**
	 * get comments tree
	 *
	 * @param $comments
	 * @param $cmtId
	 */
	public function getCommentsTree($comments, $cmtId)
	{
		$this->commentTree .= '<ul class="default-cmt__content__cmt-content row">';
		foreach ($comments as $comment) {
			if ($comment['reply_id'] == $cmtId) {
				$isReply           = (bool)$comment['is_reply'];
				$replyId           = $isReply ? $comment['reply_id'] : '';
				$userCmt           = $this->getUserComment($comment['entity_id']);
				$userName          = $userCmt->getFirstName() . ' '
					. $userCmt->getLastName();
				$countLikes        = $this->getCommentLikes($comment['comment_id']);
				$this->commentTree .= '<li class="default-cmt__content__cmt-content__cmt-row cmt-row col-xs-12'
					. ($isReply ? ' reply-row' : '') . '" data-cmt-id="'
					. $comment['comment_id'] . '" ' . ($replyId
						? 'data-reply-id="' . $replyId . '"' : '') . '>
							<div class="cmt-row__cmt-username">
								<span class="cmt-row__cmt-username username">'
					. $userName . '</span>
							</div>
							<div class="cmt-row__cmt-content">
								<p>' . $comment['content'] . '</p>
							</div>
							<div class="cmt-row__cmt-interactions interactions">
								<div class="interactions__btn-actions">
									<a class="interactions__btn-actions action btn-like" data-cmt-id="'
					. $comment['comment_id'] . '">' . __('Like') . '</a>
									<a class="interactions__btn-actions action btn-reply" data-cmt-id="'
					. $comment['comment_id'] . '">' . __('Reply') . '</a>
									<a class="interactions__btn-actions count-like">
										<i class="fa fa-thumbs-up" aria-hidden="true"></i>
										<span class="count-like__like-text">'
					. $countLikes . '</span>
									</a>
								</div>
								<div class="interactions__cmt-createdat">
									<span>' . $comment['created_at'] . '</span>
								</div>
							</div>';
				if ($comment['has_reply']) {
					$this->commentTree .= $this->getCommentsTree(
						$comments,
						$comment['comment_id']
					);
				}
				$this->commentTree .= '</li>';
			}
		}
		$this->commentTree .= '</ul>';
	}

	/**
	 * get comments tree html
	 *
	 * @return mixed
	 */
	public function getCommentsHtml()
	{
		return $this->commentTree;
	}

	/**
	 * @param $userId
	 * @return \Magento\Customer\Api\Data\CustomerInterface
	 */
	public function getUserComment($userId)
	{
		$user = $this->customerRepository->getById($userId);

		return $user;
	}

	/**
	 * @param $cmtId
	 * @return int|string
	 */
	public function getCommentLikes($cmtId)
	{
		$likes = $this->likeFactory->create()->getCollection()
			->addFieldToFilter('comment_id', $cmtId)->getSize();
		if ($likes) {
			return $likes;
		}

		return '';
	}

	/**
	 * get default image url
	 */
	public function getDefaultImageUrl()
	{
		return $this->getViewFileUrl(
			'Mageplaza_Blog::media/images/Mageplaza-logo.png'
		);
	}

	/**
	 * @param $date
	 * @param bool $monthly
	 * @return false|string
	 */
	public function getDateFormat($date, $monthly = false)
	{
		return $this->helperData->getDateFormat($date, $monthly);
	}

	public function getMonthParam()
	{
		return $this->getRequest()->getParam('month');
	}

	/**
	 * Resize Image Function
	 * @param $image
	 * @param null $width
	 * @param null $height
	 * @return string
	 */
	public function resizeImage($image, $width = null, $height = null)
	{
		$originalImageDir = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)
			->getAbsolutePath('mageplaza/blog/post/image').$image;

		if ($width == null && $height == null) {
			return $originalImageDir;
		}

		$modifiedImageDir = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath('mageplaza/resized/' . $width . '/') . $image;
		$imageResize = $this->_imageFactory->create();
		$imageResize->open($originalImageDir);
		$imageResize->constrainOnly(true);
		$imageResize->keepTransparency(true);
		$imageResize->keepFrame(false);
		$imageResize->keepAspectRatio(true);
		$imageResize->resize($width, $height);
		$imageResize->save($modifiedImageDir);

		$imageURL = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'mageplaza/resized/' . $width . '/' . $image;

		return $imageURL;
	}

	/**
	 * Get Current Theme Name Function
	 * @return string
	 */
	public function getCurrentTheme()
	{
		$themeId = $this->_scopeConfig->getValue(
			\Magento\Framework\View\DesignInterface::XML_PATH_THEME_ID,
			\Magento\Store\Model\ScopeInterface::SCOPE_STORE,
			$this->_storeManager->getStore()->getId()
		);

		/** @var $theme \Magento\Framework\View\Design\ThemeInterface */
		$theme = $this->_themeProvider->getThemeById($themeId);

		return $theme->getCode();
	}


	//************************* Page Pagination Design ***************************

	/**
	 * @return mixed
	 */
	public function getPageParam()
	{
		return $this->getRequest()->getParam('p');
	}

	/**
	 * @param $page
	 * @return string
	 */
	public function getPageLink($page)
	{

		if ($this->getMonthParam()) {
			$pageLink = $this->getUrl('*/*/*', ['_use_rewrite' => true]) . '?month=' . $this->getMonthParam() . '&p=' . $page;
		} else {
			$pageLink = $this->getUrl('*/*/*', ['_use_rewrite' => true]) . '?p=' . $page;
		}

		return $pageLink;
	}

	/**
	 * @param $currentPage
	 * @return string
	 */
	public function getPrevPage($currentPage)
	{
		$html = '';

		if ($currentPage > 1) {
			$html = '<li class="item mp-page-item">
							<a href="' . $this->getPageLink($currentPage - 1) . '" class="page">
							<span class="label">' . __('Page') . '</span>
								<span><</span>
						</a>
					</li>';
		}

		return $html;
	}

	/**
	 * @param $currentPage
	 * @return string
	 */
	public function getFirstPage($currentPage)
	{
		$html  = '';
		$start = 1;

		if ($currentPage > ($start + 2)) {

			$html = '<li class="item mp-page-item">
                    <a href="' . $this->getPageLink($start) . '" class="page">
                    <span class="label">' . __('Page') . '</span>
                        <span>' . $start . '</span>
                </a>
            </li>';
			if ($currentPage > ($start + 3)) {
				$html .= '<li class="item mp-page-item">
                        <span>...</span>
            </li>';
			}

		}

		return $html;
	}

	/**
	 * @param $currentPage
	 * @param $end
	 * @return string
	 */
	public function getLastPage($currentPage, $end)
	{
		$html = '';

		if ($currentPage < ($end - 2)) {

			if ($currentPage < ($end - 3)) {
				$html = '<li class="item mp-page-item">
                    <span>...</span>
            			</li>';
			}
			$html .= '<li class="item mp-page-item">
                <a href="' . $this->getPageLink($end) . '" class="page">
                <span class="label">' . __('Page') . '</span>
                    <span>' . $end . '</span>
            </a>
            </li>';

		}

		return $html;
	}

	/**
	 * @param $currentPage
	 * @param $countPage
	 * @return string
	 */
	public function getNextPage($currentPage, $countPage)
	{
		$html = '';


		if ($currentPage < $countPage) {

			$html = '<li class="item mp-page-item">
                    <a href="' . $this->getPageLink($currentPage + 1) . '" class="page">
                    <span class="label">' . __('Page') . '</span>
                        <span>></span>
                </a>
            </li>';


		}

		return $html;
	}

	/**
	 * @param $currentPage
	 * @param $relatedPage
	 * @param $i
	 * @return string
	 */
	public function getAllPage($currentPage, $relatedPage, $i)
	{
		$start = 1;


		if ($currentPage == $start) {
			$html = '<li class="item mp-page-item">';

			if ($currentPage == $i) {
				$html .= '<span class="selected_page">' . $i . '</span>';

			} else {

				$html .= '<a href="' . $this->getPageLink($i) . '" class="page">
                        <span class="label">' . __('Page') . '</span>
                            <span>' . $i . '</span>
                    </a>';

			}
			$html .= '</li>';

		} else {

			$html = '<li class="item mp-page-item">';

			if ($currentPage == $relatedPage) {

				$html .= '<span class="selected_page">' . $relatedPage . '</span>';

			} else {

				$html .= '<a href="' . $this->getPageLink($relatedPage) . '" class="page">
                            <span class="label">' . __('Page') . '</span>
                                <span>' . $relatedPage . '</span>
                        </a>
						</li>';

			}
		}

		return $html;
	}

}
