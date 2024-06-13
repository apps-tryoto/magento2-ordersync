<?php
/**

	Uretici..........: Oto 
	Yazılımın Adı....: Oto_PaymentCore // Oto ödeme modülleri için çekirdek modülü
	Geliştirenler....: Hidayet Ok <hidayet@tryoto.com> / Resul Aslan <resul@tryoto.com>
	Web..............: http://www.tryoto.com  //  http://www.magesanalpos.com

	/// Yasal Uyarı /////////////////////////////////////////////////////////////////////////

	Tüm hakları tryoto.com'a aittir.

	Şifrelenmiş dosyaların geri çevrimi (decompiling, dezending vs.), 
	yazılımın ücretli veya ücretsiz dağıtılması, paylaşılması, 
	yazılımın kullanım hakkı verilen site/domain dışında kullanılması, 
	lisans sisteminin herhangi bir yöntemle aldatılmaya çalışılması, 
	kullanım süresi sonundan sonra kullanılması gibi hak ihlalleri
	5846 numaralı fikir ve sanat eserleri kanununa göre
	yetkili mahkemelere intikal ettirilecektir.

	Bu yazılımı kullanan, bilgisayarına indiren, 
	sitesine kuran, özelliklerinden faydalanan, satan, sattıran, 
	aracılık eden, dağıtan her tüzel veya gerçek kişi 
	yukarıdaki şartları kabul etmiş olur.

	http://www.rega.com.tr/rega/duyuru/kanun/rega-5846.htm
	http://www.mevzuat.adalet.gov.tr/html/957.html

*/

namespace Oto\OrderSync\Model\ResourceModel;

class OrderSyncLogger extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{

	public function __construct(\Magento\Framework\Model\ResourceModel\Db\Context $context)
	{
		parent::__construct($context);
	}

	protected function _construct()
    {
        $this->_init('oto_order_jobs_log','job_log_id');
    }

}