<?php
namespace Tree\Util\Scene;
use PHPMailer\PHPMailer\PHPMailer;
use Tree\Util\HttpsCertification\Exception\ConfigFileNotExistsException;
use Tree\Util\HttpsCertification\Exception\InvalidDomainForHTTPSException;
use Tree\Util\HttpsCertification\Exception\InvalidDomainForPingException;
use Tree\Util\HttpsCertification\Exception\SendEMailErrorException;
use Tree\Util\HttpsCertification\Monitor;
class DomainsMonitoring {
    private $config;
    public function __construct(string $filename) {
        if (! file_exists($filename)) {
            throw new ConfigFileNotExistsException();
        }
        $this->config = json_decode(file_get_contents($filename));
    }
    public function doMonitor() {
        $data = $data_die = $data_bottom = $data_middle = $data_top = [];
        foreach ($this->config->domain_data as $domain_data) {
            try {
                $monitor = new Monitor($domain_data->domain, $domain_data->port ?? 443);
            } catch (InvalidDomainForPingException $e) {
                $data_die[] = [
                    'domain' => $domain_data->domain,
                    'remark' => $domain_data->remark ?? null,
                    'domain_is_available' => false,
                    'ssl_is_available' => false,
                    'expire_time' => null,
                    'expire_date' => null
                ];
                continue;
            } catch (InvalidDomainForHTTPSException $e) {
                $data_bottom[] = [
                    'domain' => $domain_data->domain,
                    'remark' => $domain_data->remark ?? null,
                    'domain_is_available' => true,
                    'ssl_is_available' => false,
                    'expire_time' => null,
                    'expire_date' => null
                ];
                continue;
            }
            $span = $monitor->getValiditySpan();
            $remind_time = strtotime(date('Y-m-d', time())) + ($this->config->before_end_day ?? 7) * 86400;
            if ($span['to'] < $remind_time) {
                $data_middle[] = [
                    'domain' => $domain_data->domain,
                    'remark' => $domain_data->remark ?? null,
                    'domain_is_available' => true,
                    'ssl_is_available' => true,
                    'expire_time' => $span['to'],
                    'expire_date' => date('Y-m-d', $span['to'])
                ];
            } else {
                $data_top[] = [
                    'domain' => $domain_data->domain,
                    'remark' => $domain_data->remark ?? null,
                    'domain_is_available' => true,
                    'ssl_is_available' => true,
                    'expire_time' => $span['to'],
                    'expire_date' => date('Y-m-d', $span['to'])
                ];
            }
        }
        $data = array_merge($data_top, $data_middle, $data_bottom, $data_die);
        return $data;
    }
    public function doMonitorAndRemindByEMail() {
        $remind_time = strtotime(date('Y-m-d', time())) + ($this->config->before_end_day ?? 7) * 86400;
        if (! isset($this->config->email)) {
            throw new SendEMailErrorException('email config not found');
        }
        $body_temp = "
<table border=\"1\">
<tr>
<td>域名</td>
<td>备注</td>
<td>域名是否可用</td>
<td>证书是否可用</td>
<td>域名有效日期</td>
</tr>
%s
</table>
";
        $tr = '';
        foreach ($this->doMonitor() as $domain_info) {
            if ($domain_info['ssl_is_available'] && $domain_info['expire_date']) {
                if ($domain_info['expire_time'] < $remind_time) {
                    $color = '#FFDAB9';
                } else {
                    $color = '#FFFFFF';
                }
            } else {
                $color = '#BEBEBE';
            }
            $tr .= sprintf("
<tr bgcolor=%s>
<td>%s</td>
<td>%s</td>
<td>%s</td>
<td>%s</td>
<td>%s</td>
</tr>
            ", $color, $domain_info['domain'], $domain_info['remark'] ?? '',
                $domain_info['domain_is_available'] ? '是' : '否', $domain_info['ssl_is_available'] ? '是' : '否',
                $domain_info['expire_date'] ?? '-');
        }
        $body = sprintf($body_temp, $tr);
        $this->sendEMail($body);
    }
    public function sendEMail(string $body) {
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->CharSet = "utf8";
        $mail->Host = $this->config->email->smtp_host;
        $mail->Port = $this->config->email->smtp_port;
        $mail->setFrom($this->config->email->username);
        $mail->SMTPAuth = true;
        $mail->Username = $this->config->email->username;
        $mail->Password = $this->config->email->password;
        $mail->SMTPSecure = "ssl";
        $mail->isHTML(true);
        foreach ($this->config->email->recipients as $recipient) {
            $mail->addAddress($recipient);
        }
        $mail->Subject = "domain remind";
        $mail->Body = $body;
        if (! $mail->send()) {
            throw new SendEMailErrorException($mail->ErrorInfo);
        }
    }
}