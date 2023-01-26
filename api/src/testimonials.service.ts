import { BaseTestimonial, Testimonial } from "./testimonial.interface";
import * as fs from "fs";

export const list = async (): Promise<Testimonial[]> => {
    return new Promise ((resolve, reject) => {
        fs.readFile(process.env.DATA_FILE,
            "utf8",
            (err, data) => {
                if (err) return reject(err);
                resolve(JSON.parse(data));
            }
        );
    })
};
export const add = async (newTestimonial: BaseTestimonial): Promise<Testimonial> => {
    return new Promise ((resolve, reject) => {
        list().then(all => {
            const testimonial = appendId(newTestimonial);
            const exists = all.some(item => item.id === testimonial.id);
            if (exists) {
                reject('Duplicate entry!');
                return;
            }

            all.push(testimonial);
            fs.writeFile(
                process.env.DATA_FILE,
                JSON.stringify(all),
                (err) => {
                    if (err) return reject(err);
                    resolve(testimonial);
                }
            );
        });
    });
};
export const find = async (searchStr: string): Promise<Testimonial[]> => {
    const existing = await list();
    const words = searchStr.toLowerCase().replace(/\s+/, ' ').trim().split(' ');
    return existing.filter(testimonial => {
        const subject =
            (testimonial.name.toLowerCase() + ' ' +
            testimonial.location.toLowerCase() + ' ' +
            testimonial.comments.toLowerCase()).replace("\n", ' ');

        let foundWords = 0;
        for (const word of words) {
            if (subject.includes(word)){
                foundWords++;
            }
        }
        return foundWords === words.length;
    });
};

const appendId = (testimonial: BaseTestimonial): Testimonial => {
    const idBase =
        testimonial.name.toLowerCase() +
        testimonial.age +
        testimonial.location.toLowerCase();
    const normalized = idBase.replace(/[^a-z0-9 ]/i, '');
    const md5 = require('md5');
    const id = md5(normalized)
    return {
        id,
        ...testimonial
    }
}